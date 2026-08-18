<?php

namespace App\Support;

use App\Models\Company;
use App\Models\LedgerEntry;
use Illuminate\Support\Collection;

class LedgerSheet
{
    public const ROWS_PER_PAGE = 18;

    public static function pages(Collection $entries, Company $company, bool $includeAccount = true): Collection
    {
        $rows = $includeAccount
            ? self::journalRows($entries, $company)
            : self::accountRows($entries, $company);

        if ($rows->isEmpty()) {
            return collect([self::padPage(collect())]);
        }

        return $rows->chunk(self::ROWS_PER_PAGE)->map(fn (Collection $chunk) => self::padPage($chunk->values()))->values();
    }

    protected static function journalRows(Collection $entries, Company $company): Collection
    {
        $rows = collect();
        foreach ($entries->groupBy(fn (LedgerEntry $e) => ($e->source_type ?: 'x').'|'.($e->source_id ?: $e->id)) as $group) {
            $debits = $group->filter(fn (LedgerEntry $e) => strcasecmp((string) $e->entry_type, 'Debit') === 0)->values();
            $credits = $group->filter(fn (LedgerEntry $e) => strcasecmp((string) $e->entry_type, 'Credit') === 0)->values();
            $n = max($debits->count(), $credits->count());
            for ($i = 0; $i < $n; $i++) {
                $rows->push([
                    'debit' => isset($debits[$i]) ? self::cell($debits[$i], $company, true) : null,
                    'credit' => isset($credits[$i]) ? self::cell($credits[$i], $company, true) : null,
                ]);
            }
        }

        return $rows->values();
    }

    protected static function accountRows(Collection $entries, Company $company): Collection
    {
        $debits = $entries->filter(fn (LedgerEntry $e) => strcasecmp((string) $e->entry_type, 'Debit') === 0)->values();
        $credits = $entries->filter(fn (LedgerEntry $e) => strcasecmp((string) $e->entry_type, 'Credit') === 0)->values();
        $n = max($debits->count(), $credits->count());
        $rows = collect();
        for ($i = 0; $i < $n; $i++) {
            $rows->push([
                'debit' => isset($debits[$i]) ? self::cell($debits[$i], $company, false) : null,
                'credit' => isset($credits[$i]) ? self::cell($credits[$i], $company, false) : null,
            ]);
        }

        return $rows;
    }

    protected static function cell(LedgerEntry $entry, Company $company, bool $includeAccount): array
    {
        $particulars = trim((string) $entry->description);
        if ($includeAccount && $entry->account) {
            $account = trim($entry->account->account_code.' '.$entry->account->account_name);
            $particulars = $particulars !== '' ? $account.' — '.$particulars : $account;
        }

        return [
            'date' => $entry->entry_date?->format('d/m/y'),
            'particulars' => $particulars,
            'folio' => $entry->reference_number,
            'discount' => '',
            'cash' => money($entry->amount, $company),
        ];
    }

    protected static function padPage(Collection $rows): Collection
    {
        while ($rows->count() < self::ROWS_PER_PAGE) {
            $rows->push(['debit' => null, 'credit' => null]);
        }

        return $rows;
    }
}
