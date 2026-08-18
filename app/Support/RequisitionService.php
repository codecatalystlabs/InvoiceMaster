<?php

namespace App\Support;

use App\Models\BudgetAllocation;
use App\Models\ChartOfAccount;
use App\Models\Department;
use App\Models\Expense;
use App\Models\PettyCashFund;
use App\Models\Requisition;
use App\Models\RequisitionLine;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequisitionService
{
    public static function submit(User $user, array $data): Requisition
    {
        $type = $data['type'] ?? 'petty_cash';
        $departmentId = $data['department_id'] ?? $user->department_id;
        self::assertDepartment($departmentId, $user->company_id);
        self::assertFund($data['petty_cash_fund_id'] ?? null, $user->company_id, $type === 'petty_cash');
        self::assertBudgetRoom($data['budget_allocation_id'] ?? null, (float) $data['amount'], $user->company_id, $departmentId);

        $req = Requisition::create([
            'number' => DocumentNumber::next('REQ', 'requisitions', 'number', $user->company_id),
            'department_id' => $departmentId,
            'budget_allocation_id' => $data['budget_allocation_id'] ?? null,
            'petty_cash_fund_id' => $type === 'petty_cash' ? ($data['petty_cash_fund_id'] ?? null) : null,
            'user_id' => $user->id,
            'title' => $data['title'],
            'purpose' => $data['purpose'] ?? null,
            'amount' => $data['amount'],
            'type' => $type,
            'status' => 'submitted',
        ]);

        self::step($req, 'submit', 'Request submitted');
        Audit::log('Submit', 'Requisition', $req->id, $req->number.' · '.$req->title, $req->amount, ['module' => 'requisitions']);

        return $req;
    }

    public static function initiate(Requisition $req, User $actor, ?string $notes = null): Requisition
    {
        self::assertReviewer($actor, $req);
        self::guard($req, ['submitted']);
        $req->update([
            'status' => 'initiated',
            'initiated_by' => $actor->id,
            'initiated_at' => now(),
        ]);
        self::step($req, 'initiate', $notes);
        Audit::log('Initiate', 'Requisition', $req->id, $req->number, $req->amount, ['module' => 'requisitions']);

        return $req;
    }

    public static function approve(Requisition $req, User $actor, ?string $notes = null): Requisition
    {
        self::assertReviewer($actor, $req);
        self::guard($req, ['initiated', 'submitted']);
        self::assertBudgetRoom($req->budget_allocation_id, (float) $req->amount, $req->company_id, $req->department_id, $req->id);
        $req->update([
            'status' => 'approved',
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);
        self::step($req, 'approve', $notes);
        Audit::log('Approve', 'Requisition', $req->id, $req->number, $req->amount, ['module' => 'requisitions']);

        return $req;
    }

    public static function reject(Requisition $req, User $actor, string $reason): Requisition
    {
        self::assertReviewer($actor, $req);
        self::guard($req, ['submitted', 'initiated', 'approved', 'disbursed', 'accounted']);

        return DB::transaction(function () use ($req, $actor, $reason) {
            if (in_array($req->status, ['disbursed', 'accounted'], true) && $req->petty_cash_fund_id && $req->type === 'petty_cash') {
                PettyCashService::post(
                    $req->fund,
                    'return',
                    (float) $req->amount,
                    'Return after reject '.$req->number,
                    $req->id
                );
            }

            $req->update([
                'status' => 'rejected',
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'reject_reason' => $reason,
            ]);
            self::step($req, 'reject', $reason);
            Audit::log('Reject', 'Requisition', $req->id, $req->number.' · '.$reason, $req->amount, ['module' => 'requisitions']);

            return $req;
        });
    }

    public static function disburse(Requisition $req, User $actor, array $data): Requisition
    {
        self::guard($req, ['approved']);
        self::assertBudgetRoom($req->budget_allocation_id, (float) $req->amount, $req->company_id, $req->department_id, $req->id);

        return DB::transaction(function () use ($req, $actor, $data) {
            if ($req->type === 'petty_cash') {
                $fundId = $data['petty_cash_fund_id'] ?? $req->petty_cash_fund_id;
                if (! $fundId) {
                    throw ValidationException::withMessages(['petty_cash_fund_id' => 'Choose the petty cash fund to issue from.']);
                }
                $fund = PettyCashFund::withoutGlobalScopes()->findOrFail($fundId);
                self::assertFund($fund->id, $req->company_id, true);
                if (! $fund->is_active) {
                    throw ValidationException::withMessages(['petty_cash_fund_id' => 'This petty cash fund is inactive.']);
                }
                $req->petty_cash_fund_id = $fund->id;
                PettyCashService::post(
                    $fund,
                    'issue',
                    (float) $req->amount,
                    'Issue for '.$req->number.' — '.$req->title,
                    $req->id
                );
            }

            $req->update([
                'status' => 'disbursed',
                'disbursed_by' => $actor->id,
                'disbursed_at' => now(),
                'disbursement_method' => $data['disbursement_method'] ?? ($req->type === 'petty_cash' ? 'Petty cash' : 'Other'),
                'petty_cash_fund_id' => $req->petty_cash_fund_id,
            ]);
            self::step($req, 'disburse', $data['notes'] ?? null, $req->amount);
            Audit::log('Disburse', 'Requisition', $req->id, $req->number, $req->amount, ['module' => 'requisitions']);

            return $req;
        });
    }

    public static function account(Requisition $req, User $actor, array $lines, ?string $notes = null): Requisition
    {
        self::guard($req, ['disbursed', 'accounted']);
        if ($req->user_id !== $actor->id && ! Requisition::reviewerCanAct($actor, $req)) {
            throw ValidationException::withMessages(['status' => 'Only the requester or a reviewer can submit accountability.']);
        }

        $lines = array_values(array_filter($lines, function ($row) {
            return filled($row['description'] ?? null) && (float) ($row['amount'] ?? 0) > 0;
        }));
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one accountability line.']);
        }

        return DB::transaction(function () use ($req, $actor, $lines, $notes) {
            $keep = $req->lines()->pluck('receipt_path', 'id');
            $req->lines()->delete();
            $total = 0.0;
            foreach ($lines as $row) {
                $path = $row['receipt_path'] ?? null;
                if (($row['receipt'] ?? null) instanceof UploadedFile) {
                    $path = $row['receipt']->store('accountability', 'public');
                }
                $amount = (float) $row['amount'];
                RequisitionLine::create([
                    'requisition_id' => $req->id,
                    'spent_on' => $row['spent_on'] ?? now()->toDateString(),
                    'description' => $row['description'],
                    'amount' => $amount,
                    'receipt_path' => $path,
                ]);
                $total += $amount;
            }
            unset($keep);
            if ($total - (float) $req->amount > 0.009) {
                throw ValidationException::withMessages(['lines' => 'Accounted spend cannot exceed the amount issued ('.money_text($req->amount).').']);
            }

            $req->update([
                'status' => 'accounted',
                'accounted_at' => now(),
                'accounted_amount' => $total,
                'accountability_notes' => $notes,
            ]);
            self::step($req, 'account', $notes, $total);
            Audit::log('Account', 'Requisition', $req->id, $req->number.' accounted '.money_text($total), $total, ['module' => 'requisitions']);

            return $req->fresh('lines');
        });
    }

    public static function close(Requisition $req, User $actor, ?string $notes = null): Requisition
    {
        self::assertReviewer($actor, $req);
        self::guard($req, ['accounted']);

        return DB::transaction(function () use ($req, $actor, $notes) {
            $req->loadMissing('fund', 'requester', 'allocation');
            $spent = (float) $req->accounted_amount;
            $remainder = (float) $req->amount - $spent;

            if ($req->type === 'petty_cash' && $req->petty_cash_fund_id && $remainder > 0.009) {
                PettyCashService::post($req->fund, 'return', $remainder, 'Unspent return for '.$req->number, $req->id);
            }

            $expense = self::postSpend($req, $spent);

            $req->update([
                'status' => 'closed',
                'closed_by' => $actor->id,
                'closed_at' => now(),
                'expense_id' => $expense?->id,
            ]);
            self::step($req, 'close', $notes, $spent);
            Audit::log('Close', 'Requisition', $req->id, $req->number.' closed', $spent, ['module' => 'requisitions']);

            return $req;
        });
    }

    protected static function postSpend(Requisition $req, float $spent): ?Expense
    {
        if ($spent <= 0) {
            return null;
        }

        $req->loadMissing('requester', 'allocation');
        $companyId = (int) $req->company_id;
        $account = ChartOfAccount::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('account_code', '5170')
            ->first();
        $method = $req->type === 'petty_cash' ? 'petty_cash' : 'cash';
        $expense = Expense::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'expense_number' => DocumentNumber::next('EXP', 'expenses', 'expense_number', $companyId),
            'expense_date' => now()->toDateString(),
            'account_id' => $account?->id,
            'vendor_name' => $req->requester?->name ?: $req->title,
            'category' => $req->allocation?->category ?: 'operations',
            'amount' => $spent,
            'payment_method' => $method,
            'payment_status' => 'Paid',
            'description' => 'Requisition '.$req->number.' — '.$req->title,
            'source_type' => 'Requisition',
            'source_id' => $req->id,
            'created_by' => auth()->id(),
        ]);
        LedgerService::postExpense($expense);

        return $expense;
    }

    protected static function assertReviewer(User $actor, Requisition $req): void
    {
        $req->loadMissing('department');
        if (! Requisition::reviewerCanAct($actor, $req)) {
            throw ValidationException::withMessages(['status' => 'You cannot review this requisition.']);
        }
    }

    protected static function assertDepartment(mixed $departmentId, int $companyId): void
    {
        if (! $departmentId) {
            return;
        }
        $ok = Department::withoutGlobalScopes()->where('id', $departmentId)->where('company_id', $companyId)->exists();
        if (! $ok) {
            throw ValidationException::withMessages(['department_id' => 'Choose a department from this company.']);
        }
    }

    protected static function assertFund(mixed $fundId, int $companyId, bool $required): void
    {
        if (! $fundId) {
            return;
        }
        $fund = PettyCashFund::withoutGlobalScopes()->where('id', $fundId)->where('company_id', $companyId)->first();
        if (! $fund) {
            throw ValidationException::withMessages(['petty_cash_fund_id' => 'Choose a petty cash fund from this company.']);
        }
        if ($required && ! $fund->is_active) {
            throw ValidationException::withMessages(['petty_cash_fund_id' => 'This petty cash fund is inactive.']);
        }
    }

    protected static function assertBudgetRoom(mixed $allocationId, float $amount, int $companyId, mixed $departmentId, ?int $excludingId = null): void
    {
        if (! $allocationId) {
            return;
        }
        $alloc = BudgetAllocation::with('budget.department')->find($allocationId);
        if (! $alloc || (int) $alloc->budget?->company_id !== $companyId) {
            throw ValidationException::withMessages(['budget_allocation_id' => 'Choose an allocation from this company.']);
        }
        if ($alloc->budget->status !== 'approved') {
            throw ValidationException::withMessages(['budget_allocation_id' => 'That budget is not approved yet.']);
        }
        if ($alloc->category === 'petty_cash') {
            throw ValidationException::withMessages(['budget_allocation_id' => 'Petty cash allocations fund the tin. Pick an operations line for this request.']);
        }
        if ($departmentId && $alloc->budget->department_id && (int) $alloc->budget->department_id !== (int) $departmentId) {
            throw ValidationException::withMessages(['budget_allocation_id' => 'That allocation belongs to '.$alloc->budget->department?->name.'.']);
        }
        if ($amount - $alloc->available($excludingId) > 0.009) {
            throw ValidationException::withMessages(['amount' => 'Only '.money_text($alloc->available($excludingId)).' is left on '.$alloc->name.'.']);
        }
    }

    protected static function guard(Requisition $req, array $statuses): void
    {
        if (! in_array($req->status, $statuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'This requisition is '.$req->status.' and cannot take that action.',
            ]);
        }
    }

    protected static function step(Requisition $req, string $step, ?string $notes = null, ?float $amount = null): void
    {
        $req->steps()->create([
            'step' => $step,
            'user_id' => auth()->id(),
            'notes' => $notes,
            'amount' => $amount,
        ]);
    }
}
