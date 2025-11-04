<?php

namespace App\Console\Commands;

use App\Services\MicrosoftService\SyncInboxEmailService;
use Illuminate\Console\Command;

class MicrosoftSyncInboxCommand extends Command
{
    protected $signature = 'microsoft:sync-inbox';

    protected $description = 'Syncs the inbox from Microsoft';

    public function handle(SyncInboxEmailService $emailService): void
    {
        $emailService->syncInboxEmails();
    }
}
