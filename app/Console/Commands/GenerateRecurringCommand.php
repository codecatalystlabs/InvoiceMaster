<?php

namespace App\Console\Commands;

use App\Support\RecurringService;
use Illuminate\Console\Command;

class GenerateRecurringCommand extends Command
{
    protected $signature = 'ops:generate-recurring';

    protected $description = 'Create due recurring invoices and vendor bills from services';

    public function handle(): int
    {
        $invoices = RecurringService::generateInvoices();
        $bills = RecurringService::generateBillsFromServices();
        $this->info("Recurring invoices: $invoices · service bills: $bills");

        return self::SUCCESS;
    }
}
