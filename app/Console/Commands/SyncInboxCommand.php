<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Support\ImapInbox;
use Illuminate\Console\Command;

class SyncInboxCommand extends Command
{
    protected $signature = 'emails:sync {--days= : How many days back to fetch}';

    protected $description = 'Fetch incoming mail from the IMAP inbox into the Emails list';

    public function handle(ImapInbox $inbox): int
    {
        $company = Company::query()->first();
        $result = $inbox->sync($company, $this->option('days') ? (int) $this->option('days') : null);

        if (! $result['ok']) {
            $this->error($result['error'] ?: 'Inbox sync failed.');

            return self::FAILURE;
        }

        $this->info("Synced {$result['synced']} new message(s); skipped {$result['skipped']} already stored.");

        return self::SUCCESS;
    }
}
