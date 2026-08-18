<?php

namespace App\Support;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\StatementImport;
use App\Models\StatementLine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankStatementService
{
    public static function import(BankAccount $account, UploadedFile $file, ?int $userId = null): StatementImport
    {
        $rows = self::parseFile($file);
        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'No transactions could be read from this file. Use a digital (text) PDF, not a scan, or a CSV with Date, Description, Amount.',
            ]);
        }

        return DB::transaction(function () use ($account, $file, $userId, $rows) {
            $import = StatementImport::create([
                'company_id' => $account->company_id,
                'bank_account_id' => $account->id,
                'filename' => $file->getClientOriginalName(),
                'line_count' => count($rows),
                'imported_by' => $userId,
            ]);

            foreach ($rows as $row) {
                $line = StatementLine::create($row + [
                    'statement_import_id' => $import->id,
                    'status' => 'unmatched',
                ]);
                self::suggestMatch($account->company_id, $line);
            }

            return $import->load('lines');
        });
    }

    public static function parseFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();
        if (! $path) {
            return [];
        }

        return $ext === 'pdf' ? self::parsePdfUpload($path) : self::parseCsv($path);
    }

    protected static function parsePdfUpload(string $path): array
    {
        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || ! str_contains($bytes, '%PDF')) {
            throw ValidationException::withMessages([
                'file' => 'That file does not look like a PDF.',
            ]);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'stmt');
        file_put_contents($tmp, $bytes);
        try {
            return self::parsePdf($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    public static function parsePdf(string $path): array
    {
        $text = PdfText::extract($path);
        if ($text === '') {
            throw ValidationException::withMessages([
                'file' => 'Could not read text from this PDF. Equity sometimes encodes statements in a way browsers can select but PHP cannot. Export CSV from Equity, or copy the table into Excel and save as CSV.',
            ]);
        }

        $rows = self::parseText($text);
        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'The PDF opened, but no transaction rows were recognised (date + amount). If this keeps happening, export CSV from Equity and upload that.',
            ]);
        }

        return $rows;
    }

    public static function parseText(string $text): array
    {
        $text = PdfText::normalize($text);
        $text = preg_replace('/(?<=\S)(?=\d{2}\/\d{2}\/\d{4})/', "\n", $text) ?? $text;
        $lines = preg_split('/\n+/', $text) ?: [];

        $buffer = '';
        $started = false;
        $raw = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || self::isJunkLine($line)) {
                continue;
            }
            if (! $started) {
                if (preg_match('/^transactions\b/i', $line) || preg_match('/transaction details/i', $line)) {
                    $started = true;
                    $buffer = '';
                    continue;
                }
                if (! preg_match('/\d{2}\/\d{2}\/\d{4}/', $line)) {
                    continue;
                }
                $started = true;
            }

            $buffer = $buffer === '' ? $line : $buffer.' '.$line;
            while ($txn = self::tryParseTxn($buffer)) {
                $buffer = $txn['_remainder'];
                unset($txn['_remainder']);
                $raw[] = $txn;
            }
            if (strlen($buffer) > 4000) {
                $buffer = substr($buffer, -1500);
            }
        }

        return self::applyBalanceSigns($raw);
    }

    protected static function isJunkLine(string $line): bool
    {
        if (preg_match('/statement date|statement period|account created|account number|account branch/i', $line)) {
            return true;
        }
        if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $line)) {
            return false;
        }

        return (bool) preg_match(
            '/^(equity|account statement|currency|transactions|transaction details|payment reference|value date|credit \(money in\)|debit \(money out\)|balance|page\s+\d|generated on|www\.|phone:|email:)/i',
            $line
        ) || (bool) preg_match('/transaction details.*value date/i', $line);
    }

    protected static function tryParseTxn(string $buffer): ?array
    {
        if (! preg_match('/^(.*?)(\d{2}\/\d{2}\/\d{4})(.*)$/s', $buffer, $m)) {
            return null;
        }

        $left = trim($m[1]);
        $date = $m[2];
        $after = $m[3];
        $trimmedAfter = ltrim($after);

        if ($trimmedAfter === '' || preg_match('/^[-–to]+\s*\d{2}\/\d{2}\/\d{4}/i', $trimmedAfter) || preg_match('/^\d{2}\/\d{2}\/\d{4}/', $trimmedAfter)) {
            $next = ltrim(substr($buffer, (int) strpos($buffer, $date) + strlen($date)));

            return $next !== '' && $next !== $buffer ? self::tryParseTxn($next) : null;
        }

        if (! preg_match_all('/\d{1,3}(?:,\d{3})+\.\d{2}|\d+\.\d{2}/', $after, $amountMatch, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        if (count($amountMatch[0]) < 2) {
            return null;
        }

        $use = 2;
        if (isset($amountMatch[0][2])) {
            $second = $amountMatch[0][1];
            $third = $amountMatch[0][2];
            $between = substr($after, $second[1] + strlen($second[0]), $third[1] - ($second[1] + strlen($second[0])));
            if (! preg_match('/[A-Za-z]|\d{2}\/\d{2}\/\d{4}/', $between)) {
                $use = 3;
            }
        }

        $picked = array_slice($amountMatch[0], 0, $use);
        $last = $picked[$use - 1];
        $numbers = array_values(array_filter(
            array_map(fn ($v) => self::parseMoney($v[0]), $picked),
            fn ($v) => $v > 0
        ));
        if (count($numbers) < 2) {
            return null;
        }

        $balance = array_pop($numbers);
        $left = trim((string) preg_replace('/\d{1,3}(?:,\d{3})+\.\d{2}|\d+\.\d{2}/', '', $left));

        $reference = '';
        $description = $left;
        if (preg_match('/^(.*?)[\s]+([A-Za-z]{0,4}\d{4,}[A-Za-z0-9]*)\s*$/', $left, $refMatch)) {
            $description = trim($refMatch[1]);
            $reference = $refMatch[2];
        }

        return [
            'line_date' => self::parseDate($date),
            'description' => trim(preg_replace('/\s+/', ' ', $description) ?? $description),
            'reference' => $reference,
            'movements' => $numbers,
            'balance' => $balance,
            '_remainder' => ltrim(substr($after, $last[1] + strlen($last[0]))),
        ];
    }

    protected static function applyBalanceSigns(array $raw): array
    {
        $prev = null;
        $rows = [];
        foreach ($raw as $row) {
            if ($prev !== null) {
                $amount = round($row['balance'] - $prev, 2);
            } elseif (count($row['movements']) >= 2) {
                $amount = round($row['movements'][0] - $row['movements'][1], 2);
            } else {
                $move = $row['movements'][0] ?? 0;
                $amount = self::guessSign((string) $row['description'], $move);
            }

            if ($amount == 0.0 && $row['description'] === '') {
                $prev = $row['balance'];
                continue;
            }

            $rows[] = [
                'line_date' => $row['line_date'],
                'description' => mb_substr((string) $row['description'], 0, 250),
                'reference' => mb_substr((string) $row['reference'], 0, 80),
                'amount' => $amount,
            ];
            $prev = $row['balance'];
        }

        return $rows;
    }

    protected static function guessSign(string $description, float $move): float
    {
        $d = strtoupper($description);
        if (preg_match('/CHARGES?\b|WITHDRAW|ATM|PURCHASE|POS|APP\/MTN|APP\/AIRTEL|TRANSFER TO|AIRTIME|\bFEE\b|\bLEVY\b|\bTAX\b/', $d)) {
            return -abs($move);
        }

        return abs($move);
    }

    protected static function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }
        $header = null;
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }
            if ($header === null) {
                $header = array_map(function ($h) {
                    $h = strtolower(trim((string) $h));
                    $h = preg_replace('/^\xEF\xBB\xBF/', '', $h) ?? $h;

                    return $h;
                }, $data);
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? '';
            }

            $credit = self::parseMoney($row['credit'] ?? $row['credit (money in)'] ?? $row['money in'] ?? 0);
            $debit = self::parseMoney($row['debit'] ?? $row['debit (money out)'] ?? $row['money out'] ?? 0);
            if ($credit > 0 || $debit > 0) {
                $amount = round($credit - $debit, 2);
            } else {
                $amount = self::parseMoney($row['amount'] ?? $row['value'] ?? 0);
            }

            $rows[] = [
                'line_date' => self::parseDate($row['date'] ?? $row['value date'] ?? $row['txn date'] ?? $row['transaction date'] ?? null),
                'description' => mb_substr((string) ($row['description'] ?? $row['narration'] ?? $row['details'] ?? $row['transaction details'] ?? ''), 0, 250),
                'reference' => mb_substr((string) ($row['reference'] ?? $row['ref'] ?? $row['cheque'] ?? $row['payment reference'] ?? ''), 0, 80),
                'amount' => $amount,
            ];
        }
        fclose($handle);

        return array_values(array_filter($rows, fn ($r) => $r['amount'] != 0.0 || $r['description'] !== ''));
    }

    public static function parseMoney(mixed $value): float
    {
        $s = trim((string) $value);
        if ($s === '' || $s === '-') {
            return 0.0;
        }
        $negative = str_starts_with($s, '(') && str_ends_with($s, ')');
        $s = str_replace([',', ' ', '(', ')'], '', $s);
        $n = (float) $s;

        return $negative ? -abs($n) : $n;
    }

    public static function parseDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'j/n/Y'] as $fmt) {
            $dt = \DateTime::createFromFormat('!'.$fmt, $value);
            if (! $dt instanceof \DateTime) {
                continue;
            }
            $errors = \DateTime::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                continue;
            }

            return $dt->format('Y-m-d');
        }

        $ts = strtotime($value);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    protected static function suggestMatch(int $companyId, StatementLine $line): void
    {
        $amount = abs((float) $line->amount);
        $ref = trim((string) $line->reference);

        $invoice = Invoice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($amount, $ref) {
                $q->where('total', $amount);
                if ($ref !== '') {
                    $q->orWhere('invoice_number', $ref);
                }
            })
            ->where('status', '!=', 'Paid')
            ->first();
        if ($invoice) {
            $line->update(['match_type' => 'Invoice', 'match_id' => $invoice->id, 'status' => 'suggested']);

            return;
        }

        $receipt = Receipt::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('amount', $amount)
            ->when($ref !== '', fn ($q) => $q->orWhere('number', $ref)->orWhere('reference_no', $ref))
            ->latest('id')
            ->first();
        if ($receipt) {
            $line->update(['match_type' => 'Receipt', 'match_id' => $receipt->id, 'status' => 'suggested']);

            return;
        }

        $expense = Expense::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('amount', $amount)
            ->latest('id')
            ->first();
        if ($expense) {
            $line->update(['match_type' => 'Expense', 'match_id' => $expense->id, 'status' => 'suggested']);
        }
    }
}
