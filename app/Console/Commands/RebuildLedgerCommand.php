<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Support\LedgerService;
use Illuminate\Console\Command;

class RebuildLedgerCommand extends Command
{
    protected $signature = 'ledger:rebuild {--company= : Company id (defaults to all)}';

    protected $description = 'Rebuild general ledger postings from invoices, receipts, expenses, services, and cash book';

    public function handle(): int
    {
        $companies = $this->option('company')
            ? Company::where('id', (int) $this->option('company'))->get()
            : Company::all();

        foreach ($companies as $company) {
            $count = LedgerService::rebuild($company->id);
            $this->info($company->name.': '.$count.' ledger lines');
        }

        return self::SUCCESS;
    }
}
