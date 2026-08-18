<?php

namespace App\Support;

use App\Models\CashBookEntry;
use Illuminate\Support\Facades\DB;

class CashBookService
{
    public static function record(array $data): CashBookEntry
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'] ?? auth()->user()->company_id;
            $last = CashBookEntry::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->orderByDesc('id')
                ->value('balance_after');

            $amount = (float) $data['amount'];
            $type = $data['type']; // debit (in) or credit (out)
            $balance = $type === 'debit' ? ((float) $last) + $amount : ((float) $last) - $amount;

            $data['balance_after'] = $balance;
            $data['company_id'] = $companyId;
            $data['number'] = $data['number'] ?? DocumentNumber::next('CB', 'cash_book_entries', 'number', $companyId);

            return CashBookEntry::withoutGlobalScopes()->create($data);
        });
    }

    public static function recomputeFrom(int $companyId, int $fromId): void
    {
        $prev = CashBookEntry::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('id', '<', $fromId)
            ->orderByDesc('id')
            ->value('balance_after');

        $running = (float) $prev;
        $later = CashBookEntry::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('id', '>=', $fromId)
            ->orderBy('id')
            ->get();

        foreach ($later as $entry) {
            $running = $entry->type === 'debit'
                ? $running + (float) $entry->amount
                : $running - (float) $entry->amount;
            $entry->balance_after = $running;
            $entry->save();
        }
    }

    public static function recomputeAll(int $companyId): void
    {
        $entries = CashBookEntry::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $running = 0.0;
        foreach ($entries as $entry) {
            $running = $entry->type === 'debit'
                ? $running + (float) $entry->amount
                : $running - (float) $entry->amount;
            if ((float) $entry->balance_after !== $running) {
                $entry->balance_after = $running;
                $entry->save();
            }
        }
    }
}
