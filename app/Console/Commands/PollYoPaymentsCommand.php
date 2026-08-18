<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Support\PaymentService;
use Illuminate\Console\Command;

class PollYoPaymentsCommand extends Command
{
    protected $signature = 'yo:poll {--minutes=45 : Ignore pending collections older than this}';

    protected $description = 'Check pending Yo Uganda collections and post receipts when they succeed';

    public function handle(): int
    {
        $pending = Payment::withoutGlobalScopes()
            ->with('invoice.company')
            ->where('provider', 'yo')
            ->where('status', 'pending')
            ->orderBy('id')
            ->get();

        $paid = 0;
        $failed = 0;
        foreach ($pending as $payment) {
            $updated = PaymentService::settleFromYo($payment);
            if ($updated->status === 'paid') {
                $paid++;
                $this->info($updated->number.' paid');
            } elseif ($updated->status === 'failed') {
                $failed++;
                $this->warn($updated->number.' failed');
            }
        }

        $this->info($pending->count().' pending · '.$paid.' paid · '.$failed.' failed');

        return self::SUCCESS;
    }
}
