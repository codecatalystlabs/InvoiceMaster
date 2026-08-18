<?php

namespace App\Support;

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
        $req = Requisition::create([
            'number' => DocumentNumber::next('REQ', 'requisitions', 'number', $user->company_id),
            'department_id' => $data['department_id'] ?? $user->department_id,
            'budget_allocation_id' => $data['budget_allocation_id'] ?? null,
            'petty_cash_fund_id' => $data['petty_cash_fund_id'] ?? null,
            'user_id' => $user->id,
            'title' => $data['title'],
            'purpose' => $data['purpose'] ?? null,
            'amount' => $data['amount'],
            'type' => $data['type'] ?? 'petty_cash',
            'status' => 'submitted',
        ]);

        self::step($req, 'submit', 'Request submitted');
        Audit::log('Submit', 'Requisition', $req->id, $req->number.' · '.$req->title, $req->amount, ['module' => 'requisitions']);

        return $req;
    }

    public static function initiate(Requisition $req, User $actor, ?string $notes = null): Requisition
    {
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
        self::guard($req, ['initiated', 'submitted']);
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
        self::guard($req, ['submitted', 'initiated', 'approved', 'accounted']);
        $req->update([
            'status' => 'rejected',
            'rejected_by' => $actor->id,
            'rejected_at' => now(),
            'reject_reason' => $reason,
        ]);
        self::step($req, 'reject', $reason);
        Audit::log('Reject', 'Requisition', $req->id, $req->number.' · '.$reason, $req->amount, ['module' => 'requisitions']);

        return $req;
    }

    public static function disburse(Requisition $req, User $actor, array $data): Requisition
    {
        self::guard($req, ['approved']);
        if (! $req->petty_cash_fund_id && ($data['type'] ?? $req->type) === 'petty_cash') {
            throw ValidationException::withMessages(['petty_cash_fund_id' => 'Choose the petty cash fund to issue from.']);
        }

        return DB::transaction(function () use ($req, $actor, $data) {
            if ($req->petty_cash_fund_id) {
                $fund = $req->fund;
                if (! empty($data['petty_cash_fund_id'])) {
                    $fund = \App\Models\PettyCashFund::findOrFail($data['petty_cash_fund_id']);
                    $req->petty_cash_fund_id = $fund->id;
                }
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
                'disbursement_method' => $data['disbursement_method'] ?? 'Petty cash',
            ]);
            self::step($req, 'disburse', $data['notes'] ?? null, $req->amount);
            Audit::log('Disburse', 'Requisition', $req->id, $req->number, $req->amount, ['module' => 'requisitions']);

            return $req;
        });
    }

    public static function account(Requisition $req, User $actor, array $lines, ?string $notes = null): Requisition
    {
        self::guard($req, ['disbursed', 'accounted']);
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one accountability line.']);
        }

        return DB::transaction(function () use ($req, $actor, $lines, $notes) {
            $req->lines()->delete();
            $total = 0.0;
            foreach ($lines as $row) {
                if (empty($row['description']) || empty($row['amount'])) {
                    continue;
                }
                $path = null;
                if (($row['receipt'] ?? null) instanceof UploadedFile) {
                    $path = $row['receipt']->store('accountability', 'public');
                }
                $amount = (float) $row['amount'];
                RequisitionLine::create([
                    'requisition_id' => $req->id,
                    'spent_on' => $row['spent_on'] ?? now()->toDateString(),
                    'description' => $row['description'],
                    'amount' => $amount,
                    'receipt_path' => $path ?? ($row['receipt_path'] ?? null),
                ]);
                $total += $amount;
            }
            if ($total <= 0) {
                throw ValidationException::withMessages(['lines' => 'Accounted amount must be greater than zero.']);
            }
            if ($total - (float) $req->amount > 0.009) {
                throw ValidationException::withMessages(['lines' => 'Accounted spend cannot exceed the amount issued ('.money($req->amount).').']);
            }

            $req->update([
                'status' => 'accounted',
                'accounted_at' => now(),
                'accounted_amount' => $total,
                'accountability_notes' => $notes,
            ]);
            self::step($req, 'account', $notes, $total);
            Audit::log('Account', 'Requisition', $req->id, $req->number.' accounted '.money($total), $total, ['module' => 'requisitions']);

            return $req->fresh('lines');
        });
    }

    public static function close(Requisition $req, User $actor, ?string $notes = null): Requisition
    {
        self::guard($req, ['accounted']);

        return DB::transaction(function () use ($req, $actor, $notes) {
            $spent = (float) $req->accounted_amount;
            $remainder = (float) $req->amount - $spent;

            if ($req->petty_cash_fund_id) {
                $fund = $req->fund;
                if ($spent > 0) {
                    PettyCashService::post($fund, 'spend', $spent, 'Spend for '.$req->number, $req->id);
                }
                if ($remainder > 0.009) {
                    PettyCashService::post($fund, 'return', $remainder, 'Unspent return for '.$req->number, $req->id);
                }
            }

            $req->update([
                'status' => 'closed',
                'closed_by' => $actor->id,
                'closed_at' => now(),
            ]);
            self::step($req, 'close', $notes, $spent);
            Audit::log('Close', 'Requisition', $req->id, $req->number.' closed', $spent, ['module' => 'requisitions']);

            return $req;
        });
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
