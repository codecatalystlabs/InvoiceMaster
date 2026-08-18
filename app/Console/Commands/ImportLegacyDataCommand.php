<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetValuation;
use App\Models\AuditLog;
use App\Models\CashBookEntry;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LedgerEntry;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Receipt;
use App\Models\Service;
use App\Models\ServicePayment;
use App\Models\User;
use App\Support\CashBookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ImportLegacyDataCommand extends Command
{
    protected $signature = 'data:import-legacy
        {--invoice-db= : Old invoice app database (defaults to LEGACY_INVOICE_DB or invoice_system)}
        {--codecash-db= : InvoiceMaster / CodeCash database (defaults to LEGACY_CODECASH_DB or codecash)}
        {--host= : MySQL host (defaults to DB_HOST)}
        {--username= : MySQL user (defaults to DB_USERNAME)}
        {--password= : MySQL password (defaults to DB_PASSWORD)}
        {--only= : Limit to invoice or codecash}
        {--dry-run : Count source rows without writing}';

    protected $description = 'Import data from the old invoice_system and codecash databases into this Laravel app';

    protected Company $company;

    /** @var array<int,int> */
    protected array $invoiceUsers = [];

    /** @var array<int,int> */
    protected array $codecashUsers = [];

    /** @var array<int,int> */
    protected array $clients = [];

    /** @var array<int,int> */
    protected array $quotations = [];

    /** @var array<int,int> */
    protected array $invoices = [];

    /** @var array<int,int> */
    protected array $accounts = [];

    /** @var array<int,int> */
    protected array $expenses = [];

    /** @var array<int,int> */
    protected array $assets = [];

    /** @var array<int,int> */
    protected array $services = [];

    public function handle(): int
    {
        $this->registerConnection('legacy_invoice', $this->invoiceDatabase());
        $this->registerConnection('legacy_codecash', $this->codecashDatabase());

        $this->company = $this->ensureCompany();
        ChartOfAccount::seedDefaults($this->company->id);

        if ($this->option('dry-run')) {
            $this->preview();

            return self::SUCCESS;
        }

        DB::connection()->disableQueryLog();
        $only = $this->option('only');

        if ($only !== 'codecash') {
            if ($this->databaseExists('legacy_invoice')) {
                $this->info('Importing invoice_system…');
                $this->importInvoiceSystem();
            } else {
                $this->warn('Skipping invoice_system — database "'.$this->invoiceDatabase().'" is not reachable.');
            }
        }

        if ($only !== 'invoice') {
            if ($this->databaseExists('legacy_codecash')) {
                $this->info('Importing InvoiceMaster (codecash)…');
                $this->importCodecash();
            } else {
                $this->warn('Skipping codecash — database "'.$this->codecashDatabase().'" is not reachable.');
            }
        }

        CashBookService::recomputeAll($this->company->id);
        $this->info('Cash book balances recomputed.');
        $ledgerCount = \App\Support\LedgerService::rebuild($this->company->id);
        $this->info("  ledger: $ledgerCount lines rebuilt from invoices, receipts, expenses, and cash book.");
        $this->info('Done. Open the app and confirm receipts, cash book, and ledger.');

        return self::SUCCESS;
    }

    protected function registerConnection(string $name, string $database): void
    {
        config([
            "database.connections.$name" => [
                'driver' => 'mysql',
                'host' => $this->option('host') ?: env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => $database,
                'username' => $this->option('username') ?: env('DB_USERNAME', 'root'),
                'password' => $this->option('password') ?? env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ],
        ]);
    }

    protected function databaseExists(string $connection): bool
    {
        try {
            DB::connection($connection)->getPdo();

            return true;
        } catch (Throwable $e) {
            $this->line('  '.$e->getMessage());

            return false;
        }
    }

    protected function hasSourceTable(string $connection, string $table): bool
    {
        return Schema::connection($connection)->hasTable($table);
    }

    protected function invoiceDatabase(): string
    {
        return (string) ($this->option('invoice-db') ?: env('LEGACY_INVOICE_DB', 'invoice_system'));
    }

    protected function codecashDatabase(): string
    {
        return (string) ($this->option('codecash-db') ?: env('LEGACY_CODECASH_DB', 'codecash'));
    }

    protected function ensureCompany(): Company
    {
        return Company::firstOrCreate(
            ['email' => 'info@codecatalystug.com'],
            [
                'name' => 'Code Catalyst Labs',
                'address' => "Mug House One, Kanjokya Street,\nKamwokya, Kampala district",
                'phone' => '+256 773 078860, +256 783261162',
                'currency' => 'UGX',
                'tagline' => "BUILDING TOMORROW'S SOLUTIONS TODAY",
                'services_line' => "Computer Systems, Development, Management, Maintenance\nAdministration And Consultive Support",
            ]
        );
    }

    protected function preview(): void
    {
        foreach (['legacy_invoice' => $this->invoiceDatabase(), 'legacy_codecash' => $this->codecashDatabase()] as $conn => $db) {
            if (! $this->databaseExists($conn)) {
                $this->warn("$db is not reachable.");
                continue;
            }
            $this->info("Source $db:");
            foreach (DB::connection($conn)->select('SHOW TABLES') as $row) {
                $table = array_values((array) $row)[0];
                $count = DB::connection($conn)->table($table)->count();
                $this->line("  $table: $count");
            }
        }
    }

    protected function importInvoiceSystem(): void
    {
        $c = 'legacy_invoice';
        $this->importInvoiceUsers($c);
        $this->importClients($c);
        $this->importQuotations($c);
        $this->importInvoiceSystemInvoices($c);
        $this->importAccounts($c);
        $this->importExpenses($c);
        $this->importAssets($c);
        $this->importServices($c);
        $this->importInvoiceCashbook($c);
        $this->importLedger($c);
        $this->importEmails($c);
        $this->importAudit($c);
    }

    protected function importCodecash(): void
    {
        $c = 'legacy_codecash';
        $this->importCodecashUsers($c);
        $this->importCodecashInvoices($c);
        $this->importReceipts($c);
        $this->importCodecashCashbook($c);
    }

    protected function mapRole(?string $role): string
    {
        $role = strtolower((string) $role);

        return match ($role) {
            'admin', 'owner' => 'Admin',
            'finance' => 'Finance',
            'sales', 'staff', 'member' => 'Sales',
            default => in_array($role, ['admin', 'finance', 'sales'], true) ? ucfirst($role) : 'Sales',
        };
    }

    protected function col(object $row, string $key, $default = null)
    {
        return property_exists($row, $key) ? $row->{$key} : $default;
    }

    protected function userId(?int $oldId, array $map, bool $fallback = true): ?int
    {
        if ($oldId && isset($map[$oldId])) {
            return $map[$oldId];
        }

        return $fallback ? User::where('company_id', $this->company->id)->value('id') : null;
    }

    protected function uniqueDocNumber(string $model, string $column, string $number, int $sourceId): string
    {
        $exists = fn (string $num) => $model::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where($column, $num)
            ->exists();
        if (! $exists($number)) {
            return $number;
        }
        $candidate = $number.'-CC'.$sourceId;
        $i = 2;
        while ($exists($candidate)) {
            $candidate = $number.'-CC'.$sourceId.'-'.$i++;
        }

        return $candidate;
    }

    protected function importUserRow(object $row, string $password, string $name, array &$map): void
    {
        $existing = User::where('email', $row->email)->first();
        if ($existing) {
            $map[$row->id] = $existing->id;
            if (! $existing->company_id) {
                $existing->update(['company_id' => $this->company->id]);
            }

            return;
        }

        $id = DB::table('users')->insertGetId([
            'company_id' => $this->company->id,
            'name' => $name,
            'email' => $row->email,
            'password' => $password ?: bcrypt('admin123'),
            'role' => $this->mapRole($this->col($row, 'role', 'Sales')),
            'status' => $this->col($row, 'status', 'Active') === 'Inactive' ? 'Inactive' : 'Active',
            'created_at' => $this->col($row, 'created_at', now()),
            'updated_at' => now(),
        ]);
        $map[$row->id] = $id;
        $this->line("  user {$row->email}");
    }

    protected function importInvoiceUsers(string $c): void
    {
        if (! $this->hasSourceTable($c, 'users')) {
            return;
        }
        foreach (DB::connection($c)->table('users')->orderBy('id')->get() as $row) {
            $this->importUserRow($row, $row->password, $row->username ?? $row->name ?? $row->email, $this->invoiceUsers);
        }
    }

    protected function importCodecashUsers(string $c): void
    {
        if (! $this->hasSourceTable($c, 'users')) {
            return;
        }
        foreach (DB::connection($c)->table('users')->orderBy('id')->get() as $row) {
            $hash = $this->col($row, 'password_hash') ?: $this->col($row, 'password', '');
            $this->importUserRow($row, $hash, $row->name ?? $row->email, $this->codecashUsers);
        }
    }

    protected function importClients(string $c): void
    {
        if (! $this->hasSourceTable($c, 'clients')) {
            return;
        }
        foreach (DB::connection($c)->table('clients')->orderBy('id')->get() as $row) {
            $client = Client::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $this->company->id,
                    'name' => $row->name,
                    'email' => $row->email,
                ],
                [
                    'phone' => $row->phone ?? null,
                    'company' => $row->company ?? null,
                    'address' => $this->col($row, 'address'),
                ]
            );
            $this->clients[$row->id] = $client->id;
        }
        $this->info('  clients: '.count($this->clients));
    }

    protected function importQuotations(string $c): void
    {
        if (! $this->hasSourceTable($c, 'quotations')) {
            return;
        }
        foreach (DB::connection($c)->table('quotations')->orderBy('id')->get() as $row) {
            $clientId = $this->clients[$row->client_id] ?? null;
            if (! $clientId) {
                continue;
            }
            $quotation = Quotation::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $this->company->id, 'quotation_number' => $row->quotation_number],
                [
                    'client_id' => $clientId,
                    'date' => $row->date,
                    'subtotal' => $row->subtotal,
                    'tax' => $row->tax,
                    'discount' => $row->discount,
                    'total' => $row->total,
                    'status' => $row->status,
                    'notes' => $row->notes,
                    'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->invoiceUsers),
                    'created_at' => $row->created_at ?? now(),
                ]
            );
            $this->quotations[$row->id] = $quotation->id;
            if ($quotation->wasRecentlyCreated && $this->hasSourceTable($c, 'quotation_items')) {
                foreach (DB::connection($c)->table('quotation_items')->where('quotation_id', $row->id)->get() as $item) {
                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'item_name' => $item->item_name,
                        'qty' => $item->qty,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total,
                    ]);
                }
            }
        }
        $this->info('  quotations: '.count($this->quotations));
    }

    protected function importInvoiceSystemInvoices(string $c): void
    {
        if (! $this->hasSourceTable($c, 'invoices')) {
            return;
        }
        foreach (DB::connection($c)->table('invoices')->orderBy('id')->get() as $row) {
            $invoice = Invoice::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $this->company->id, 'invoice_number' => $row->invoice_number],
                [
                    'quotation_id' => isset($row->quotation_id) ? ($this->quotations[$row->quotation_id] ?? null) : null,
                    'client_id' => $this->clients[$row->client_id] ?? null,
                    'client_name' => null,
                    'date' => $row->date,
                    'due_date' => $row->due_date,
                    'subtotal' => $row->subtotal,
                    'tax' => $row->tax,
                    'discount' => $row->discount,
                    'total' => $row->total,
                    'status' => $row->status,
                    'notes' => $row->notes,
                    'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->invoiceUsers),
                    'created_at' => $row->created_at ?? now(),
                ]
            );
            $this->invoices[$row->id] = $invoice->id;
            if ($invoice->wasRecentlyCreated && $this->hasSourceTable($c, 'invoice_items')) {
                foreach (DB::connection($c)->table('invoice_items')->where('invoice_id', $row->id)->get() as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'item_name' => $item->item_name,
                        'qty' => $item->qty,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total,
                    ]);
                }
            }
        }
        $this->info('  invoices: '.count($this->invoices));
    }

    protected function importAccounts(string $c): void
    {
        if (! $this->hasSourceTable($c, 'chart_of_accounts')) {
            return;
        }
        foreach (DB::connection($c)->table('chart_of_accounts')->orderBy('id')->get() as $row) {
            $account = ChartOfAccount::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $this->company->id, 'account_code' => $row->account_code],
                [
                    'account_name' => $row->account_name,
                    'account_type' => $row->account_type,
                    'description' => $row->description,
                    'is_active' => (bool) ($row->is_active ?? true),
                ]
            );
            $this->accounts[$row->id] = $account->id;
        }
        $this->info('  accounts: '.count($this->accounts));
    }

    protected function importExpenses(string $c): void
    {
        if (! $this->hasSourceTable($c, 'expenses')) {
            return;
        }
        foreach (DB::connection($c)->table('expenses')->orderBy('id')->get() as $row) {
            $expense = Expense::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $this->company->id, 'expense_number' => $row->expense_number],
                [
                    'expense_date' => $row->expense_date,
                    'account_id' => $this->accounts[$row->account_id] ?? ChartOfAccount::withoutGlobalScopes()->where('company_id', $this->company->id)->where('account_code', '5110')->value('id'),
                    'vendor_name' => $row->vendor_name,
                    'category' => $row->category,
                    'amount' => $row->amount,
                    'payment_method' => $row->payment_method,
                    'payment_status' => $row->payment_status,
                    'is_recurring' => (bool) $row->is_recurring,
                    'recurrence_frequency' => $row->recurrence_frequency,
                    'next_recurrence_date' => $row->next_recurrence_date,
                    'description' => $row->description,
                    'receipt_file' => $row->receipt_file,
                    'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->invoiceUsers),
                    'created_at' => $row->created_at ?? now(),
                ]
            );
            $this->expenses[$row->id] = $expense->id;
        }
        $this->info('  expenses: '.count($this->expenses));
    }

    protected function importAssets(string $c): void
    {
        if (! $this->hasSourceTable($c, 'assets')) {
            return;
        }
        foreach (DB::connection($c)->table('assets')->orderBy('id')->get() as $row) {
            $asset = Asset::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $this->company->id, 'asset_number' => $row->asset_number],
                [
                    'asset_name' => $row->asset_name,
                    'category' => $row->category,
                    'purchase_date' => $row->purchase_date,
                    'purchase_price' => $row->purchase_price,
                    'current_value' => $row->current_value,
                    'depreciation_rate' => $row->depreciation_rate,
                    'depreciation_method' => $row->depreciation_method,
                    'location' => $row->location,
                    'condition_status' => $row->condition_status,
                    'description' => $row->description,
                    'serial_number' => $row->serial_number,
                    'warranty_expiry' => $row->warranty_expiry,
                    'assigned_to' => $this->userId($this->col($row, 'assigned_to') ? (int) $row->assigned_to : null, $this->invoiceUsers, false),
                    'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->invoiceUsers),
                    'created_at' => $row->created_at ?? now(),
                ]
            );
            $this->assets[$row->id] = $asset->id;
        }
        if ($this->hasSourceTable($c, 'asset_valuations')) {
            foreach (DB::connection($c)->table('asset_valuations')->orderBy('id')->get() as $row) {
                $assetId = $this->assets[$row->asset_id] ?? null;
                if (! $assetId) {
                    continue;
                }
                AssetValuation::firstOrCreate(
                    ['asset_id' => $assetId, 'valuation_date' => $row->valuation_date, 'valuation_amount' => $row->valuation_amount],
                    [
                        'valuation_reason' => $row->valuation_reason,
                        'valued_by' => $this->userId($this->col($row, 'valued_by') ? (int) $row->valued_by : null, $this->invoiceUsers),
                    ]
                );
            }
        }
        $this->info('  assets: '.count($this->assets));
    }

    protected function importServices(string $c): void
    {
        if (! $this->hasSourceTable($c, 'services')) {
            return;
        }
        foreach (DB::connection($c)->table('services')->orderBy('id')->get() as $row) {
            $service = Service::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $this->company->id, 'service_number' => $row->service_number],
                [
                    'service_name' => $row->service_name,
                    'provider_name' => $row->provider_name,
                    'provider_contact' => $row->provider_contact,
                    'category' => $row->category,
                    'cost' => $row->cost,
                    'billing_frequency' => $row->billing_frequency,
                    'start_date' => $row->start_date,
                    'end_date' => $row->end_date,
                    'next_billing_date' => $row->next_billing_date,
                    'auto_renew' => (bool) $row->auto_renew,
                    'status' => $row->status,
                    'description' => $row->description,
                    'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->invoiceUsers),
                    'created_at' => $row->created_at ?? now(),
                ]
            );
            $this->services[$row->id] = $service->id;
        }
        if ($this->hasSourceTable($c, 'service_payments')) {
            foreach (DB::connection($c)->table('service_payments')->orderBy('id')->get() as $row) {
                $serviceId = $this->services[$row->service_id] ?? null;
                if (! $serviceId) {
                    continue;
                }
                ServicePayment::firstOrCreate(
                    ['service_id' => $serviceId, 'payment_date' => $row->payment_date, 'amount' => $row->amount, 'reference_number' => $row->reference_number],
                    [
                        'payment_method' => $row->payment_method,
                        'notes' => $row->notes,
                        'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->invoiceUsers),
                    ]
                );
            }
        }
        $this->info('  services: '.count($this->services));
    }

    protected function importInvoiceCashbook(string $c): void
    {
        if (! $this->hasSourceTable($c, 'cashbook')) {
            return;
        }
        $count = 0;
        foreach (DB::connection($c)->table('cashbook')->orderBy('id')->get() as $row) {
            $type = in_array($row->transaction_type, ['Income', 'income'], true) ? 'debit' : 'credit';
            $exists = CashBookEntry::withoutGlobalScopes()
                ->where('company_id', $this->company->id)
                ->where('number', $row->reference_number)
                ->exists();
            if ($exists) {
                continue;
            }
            CashBookEntry::withoutGlobalScopes()->create([
                'company_id' => $this->company->id,
                'number' => $row->reference_number,
                'entry_date' => $row->transaction_date,
                'description' => $row->description ?: $row->category ?: $row->transaction_type,
                'type' => $type,
                'amount' => $row->amount,
                'balance_after' => 0,
                'account_id' => $this->accounts[$row->account_id] ?? null,
                'payment_method' => $row->payment_method,
                'invoice_id' => isset($row->invoice_id) ? ($this->invoices[$row->invoice_id] ?? null) : null,
                'expense_id' => isset($row->expense_id) ? ($this->expenses[$row->expense_id] ?? null) : null,
                'service_id' => isset($row->service_id) ? ($this->services[$row->service_id] ?? null) : null,
                'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->invoiceUsers),
                'created_at' => $row->created_at ?? now(),
            ]);
            $count++;
        }
        $this->info("  invoice cashbook: $count");
    }

    protected function importLedger(string $c): void
    {
        if (! $this->hasSourceTable($c, 'ledger_entries')) {
            return;
        }
        $count = 0;
        foreach (DB::connection($c)->table('ledger_entries')->orderBy('id')->get() as $row) {
            $accountId = $this->accounts[$row->account_id] ?? null;
            if (! $accountId) {
                continue;
            }
            $exists = LedgerEntry::withoutGlobalScopes()
                ->where('company_id', $this->company->id)
                ->where('reference_number', $row->reference_number)
                ->where('account_id', $accountId)
                ->where('entry_type', $row->entry_type)
                ->whereDate('entry_date', $row->entry_date)
                ->where('amount', $row->amount)
                ->exists();
            if ($exists) {
                continue;
            }
            LedgerEntry::withoutGlobalScopes()->create([
                'company_id' => $this->company->id,
                'entry_date' => $row->entry_date,
                'reference_number' => $row->reference_number,
                'account_id' => $accountId,
                'entry_type' => $row->entry_type,
                'amount' => $row->amount,
                'description' => $row->description,
                'source_type' => $row->source_type,
                'source_id' => $row->source_id,
                'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->invoiceUsers),
                'created_at' => $row->created_at ?? now(),
            ]);
            $count++;
        }
        $this->info("  ledger: $count");
    }

    protected function importEmails(string $c): void
    {
        if (! $this->hasSourceTable($c, 'emails')) {
            return;
        }
        $count = 0;
        foreach (DB::connection($c)->table('emails')->orderBy('id')->get() as $row) {
            $exists = EmailMessage::withoutGlobalScopes()
                ->where('company_id', $this->company->id)
                ->where('subject', $row->subject)
                ->where('to_email', $row->to_email)
                ->where('sent_at', $row->sent_at)
                ->exists();
            if ($exists) {
                continue;
            }
            EmailMessage::withoutGlobalScopes()->create([
                'company_id' => $this->company->id,
                'message_id' => $row->message_id ?? null,
                'reference_type' => $row->reference_type ?? 'general',
                'reference_id' => $row->reference_id ?? null,
                'direction' => $row->direction ?? 'outgoing',
                'from_email' => $row->from_email,
                'from_name' => $row->from_name,
                'to_email' => $row->to_email,
                'subject' => $row->subject,
                'body_html' => $row->body_html ?? $row->body_text,
                'status' => $row->status ?? 'sent',
                'error_message' => $row->error_message ?? null,
                'sent_by' => $this->userId($this->col($row, 'sent_by') ? (int) $row->sent_by : null, $this->invoiceUsers, false),
                'sent_at' => $row->sent_at,
                'created_at' => $row->created_at ?? now(),
            ]);
            $count++;
        }
        $this->info("  emails: $count");
    }

    protected function importAudit(string $c): void
    {
        if (! $this->hasSourceTable($c, 'audit_logs')) {
            return;
        }
        $count = 0;
        foreach (DB::connection($c)->table('audit_logs')->orderBy('id')->get() as $row) {
            $userId = $this->userId($row->user_id ?? null, $this->invoiceUsers);
            if (! $userId) {
                continue;
            }
            AuditLog::withoutGlobalScopes()->create([
                'company_id' => $this->company->id,
                'user_id' => $userId,
                'action' => $row->action,
                'entity_type' => $row->entity_type,
                'entity_id' => $row->entity_id,
                'details' => $row->details,
                'created_at' => $row->timestamp ?? $row->created_at ?? now(),
            ]);
            $count++;
        }
        $this->info("  audit: $count");
    }

    protected function importCodecashInvoices(string $c): void
    {
        if (! $this->hasSourceTable($c, 'invoices')) {
            return;
        }
        $count = 0;
        foreach (DB::connection($c)->table('invoices')->orderBy('id')->get() as $row) {
            $sourceNumber = $row->number ?? $row->invoice_number;
            $existing = Invoice::withoutGlobalScopes()
                ->where('company_id', $this->company->id)
                ->where(function ($q) use ($row, $sourceNumber) {
                    $q->where('invoice_number', $sourceNumber)
                        ->orWhere('invoice_number', $sourceNumber.'-CC'.$row->id)
                        ->orWhere(function ($w) use ($row) {
                            $w->where('client_name', $row->client_name)
                                ->where('total', $row->grand_total ?? $row->total ?? 0)
                                ->whereDate('date', $row->issued_date ?? $row->date);
                        });
                })
                ->first();
            if ($existing) {
                $this->invoices[$row->id] = $existing->id;
                continue;
            }
            $number = $this->uniqueDocNumber(Invoice::class, 'invoice_number', $sourceNumber, (int) $row->id);
            $invoice = Invoice::withoutGlobalScopes()->create([
                'company_id' => $this->company->id,
                'invoice_number' => $number,
                'client_name' => $row->client_name,
                'client_contact' => $row->client_contact ?? null,
                'date' => $row->issued_date ?? $row->date,
                'due_date' => $row->due_date ?? null,
                'subtotal' => $row->subtotal ?? 0,
                'tax' => $row->tax_total ?? $row->tax ?? 0,
                'discount' => $row->discount ?? 0,
                'total' => $row->grand_total ?? $row->total ?? 0,
                'status' => $row->status,
                'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->codecashUsers),
                'created_at' => $row->created_at ?? now(),
            ]);
            $this->invoices[$row->id] = $invoice->id;
            if ($this->hasSourceTable($c, 'invoice_items')) {
                foreach (DB::connection($c)->table('invoice_items')->where('invoice_id', $row->id)->get() as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'item_name' => $item->description ?? $item->item_name,
                        'qty' => $item->quantity ?? $item->qty ?? 1,
                        'unit_price' => $item->unit_price,
                        'total' => $item->line_total ?? $item->total,
                    ]);
                }
            }
            $count++;
        }
        $this->info("  codecash invoices: $count");
    }

    protected function importReceipts(string $c): void
    {
        if (! $this->hasSourceTable($c, 'receipts')) {
            return;
        }
        $count = 0;
        foreach (DB::connection($c)->table('receipts')->orderBy('id')->get() as $row) {
            $same = Receipt::withoutGlobalScopes()
                ->where('company_id', $this->company->id)
                ->where('client_name', $row->client_name)
                ->where('amount', $row->amount)
                ->whereDate('issued_date', $row->issued_date)
                ->exists();
            if ($same) {
                continue;
            }
            $number = $this->uniqueDocNumber(Receipt::class, 'number', $row->number, (int) $row->id);
            Receipt::withoutGlobalScopes()->create([
                'company_id' => $this->company->id,
                'number' => $number,
                'client_name' => $row->client_name,
                'client_contact' => $row->client_contact ?? null,
                'description' => $row->description ?? null,
                'amount' => $row->amount,
                'payment_method' => $row->payment_method ?? 'cash',
                'issued_date' => $row->issued_date,
                'reference_no' => $this->col($row, 'reference_no'),
                'balance' => $this->col($row, 'balance'),
                'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->codecashUsers),
                'created_at' => $row->created_at ?? now(),
            ]);
            $count++;
        }
        $this->info("  receipts: $count");
    }

    protected function importCodecashCashbook(string $c): void
    {
        if (! $this->hasSourceTable($c, 'cashbooks')) {
            return;
        }
        $count = 0;
        foreach (DB::connection($c)->table('cashbooks')->orderBy('id')->get() as $row) {
            $type = $row->type;
            if ($type === 'in') {
                $type = 'debit';
            } elseif ($type === 'out') {
                $type = 'credit';
            }
            $same = CashBookEntry::withoutGlobalScopes()
                ->where('company_id', $this->company->id)
                ->where('description', $row->description)
                ->where('amount', $row->amount)
                ->where('type', $type)
                ->whereDate('entry_date', $row->entry_date)
                ->exists();
            if ($same) {
                continue;
            }
            $number = $this->uniqueDocNumber(CashBookEntry::class, 'number', $row->number, (int) $row->id);
            CashBookEntry::withoutGlobalScopes()->create([
                'company_id' => $this->company->id,
                'number' => $number,
                'entry_date' => $row->entry_date,
                'description' => $row->description,
                'folio' => $this->col($row, 'folio'),
                'discount_allowed' => $this->col($row, 'discount_allowed', 0),
                'type' => $type,
                'amount' => $row->amount,
                'balance_after' => $row->balance_after ?? 0,
                'created_by' => $this->userId($this->col($row, 'created_by') ? (int) $row->created_by : null, $this->codecashUsers),
                'created_at' => $row->created_at ?? now(),
            ]);
            $count++;
        }
        $this->info("  codecash cash book: $count");
    }
}
