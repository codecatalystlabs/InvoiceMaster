<?php

namespace App\Support;

use App\Models\PettyCashEntry;
use App\Models\PettyCashFund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PettyCashService
{
    public static function post(PettyCashFund $fund, string $type, float $amount, string $description, ?int $requisitionId = null, ?string $date = null): PettyCashEntry
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        $in = in_array($type, ['allocation', 'return', 'replenish'], true);

        return DB::transaction(function () use ($fund, $type, $amount, $description, $requisitionId, $date, $in) {
            $fund = PettyCashFund::withoutGlobalScopes()->lockForUpdate()->findOrFail($fund->id);
            $next = $in
                ? (float) $fund->balance + $amount
                : (float) $fund->balance - $amount;

            if ($next < -0.001) {
                throw ValidationException::withMessages(['amount' => 'Petty cash fund does not have enough balance.']);
            }
            if ($in && $fund->float_limit > 0 && $next > (float) $fund->float_limit + 0.001) {
                throw ValidationException::withMessages(['amount' => 'This would exceed the fund float limit of '.money($fund->float_limit).'.']);
            }

            $fund->balance = $next;
            $fund->save();

            $entry = PettyCashEntry::create([
                'petty_cash_fund_id' => $fund->id,
                'requisition_id' => $requisitionId,
                'number' => DocumentNumber::next('PC', 'petty_cash_entries', 'number', $fund->company_id),
                'entry_date' => $date ?: now()->toDateString(),
                'type' => $type,
                'description' => $description,
                'amount' => $amount,
                'balance_after' => $next,
            ]);

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
}
