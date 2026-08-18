<?php

namespace App\Support;

use App\Models\BudgetAllocation;
use App\Models\PettyCashEntry;
use App\Models\PettyCashFund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PettyCashService
{
    public static function post(
        PettyCashFund $fund,
        string $type,
        float $amount,
        string $description,
        ?int $requisitionId = null,
        ?string $date = null,
        ?int $allocationId = null
    ): PettyCashEntry {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        $in = in_array($type, ['allocation', 'return', 'replenish'], true);
        $movesTin = $type !== 'spend';

        return DB::transaction(function () use ($fund, $type, $amount, $description, $requisitionId, $date, $allocationId, $in, $movesTin) {
            $fund = PettyCashFund::withoutGlobalScopes()->lockForUpdate()->findOrFail($fund->id);
            if (! $fund->is_active && in_array($type, ['allocation', 'replenish', 'issue'], true)) {
                throw ValidationException::withMessages(['fund' => 'This petty cash fund is inactive.']);
            }

            if ($allocationId) {
                self::assertAllocation($allocationId, $fund->company_id, $amount);
            }

            $next = (float) $fund->balance;
            if ($movesTin) {
                $next = $in ? $next + $amount : $next - $amount;
            }
            if ($next < -0.001) {
                throw ValidationException::withMessages(['amount' => 'Petty cash fund does not have enough balance.']);
            }
            if ($in && $fund->hasFloatCap() && $next > (float) $fund->float_limit + 0.001) {
                throw ValidationException::withMessages(['amount' => 'This would exceed the fund float limit of '.money_text($fund->float_limit).'.']);
            }

            $fund->balance = $next;
            $fund->save();

            $entry = PettyCashEntry::create([
                'petty_cash_fund_id' => $fund->id,
                'requisition_id' => $requisitionId,
                'budget_allocation_id' => $allocationId,
                'number' => DocumentNumber::next('PC', 'petty_cash_entries', 'number', $fund->company_id),
                'entry_date' => $date ?: now()->toDateString(),
                'type' => $type,
                'description' => $description,
                'amount' => $amount,
                'balance_after' => $next,
            ]);

            if (in_array($type, ['allocation', 'replenish'], true)) {
                LedgerService::postPettyCashTopup($entry->fresh('fund'));
            }

            Audit::log(
                ucfirst($type),
                'PettyCash',
                $entry->id,
                $fund->name.' · '.$description,
                $amount,
                ['module' => 'petty-cash', 'fund_id' => $fund->id, 'type' => $type]
            );

            return $entry;
        });
    }

    protected static function assertAllocation(int $allocationId, int $companyId, float $amount): void
    {
        $alloc = BudgetAllocation::with('budget')->find($allocationId);
        if (! $alloc || (int) $alloc->budget?->company_id !== $companyId) {
            throw ValidationException::withMessages(['budget_allocation_id' => 'Choose an allocation from this company.']);
        }
        if ($alloc->budget->status !== 'approved') {
            throw ValidationException::withMessages(['budget_allocation_id' => 'That budget is not approved yet.']);
        }
        if ($alloc->category !== 'petty_cash') {
            throw ValidationException::withMessages(['budget_allocation_id' => 'Top-ups must use a Petty cash allocation.']);
        }
        if ($amount - $alloc->available() > 0.009) {
            throw ValidationException::withMessages(['amount' => 'Only '.money_text($alloc->available()).' is left on '.$alloc->name.'.']);
        }
    }
}
