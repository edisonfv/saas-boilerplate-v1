<?php

namespace App\Console\Commands;

use App\Services\Support\SupportTicketManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('support:close-resolved')]
#[Description('Close resolved support tickets with no follow-up after the configured number of days.')]
class CloseResolvedSupportTickets extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SupportTicketManager $manager): int
    {
        $closed = $manager->closeStaleResolved();

        $this->info($closed === 0 ? 'No resolved tickets to close.' : "Closed {$closed} resolved ticket(s).");

        return self::SUCCESS;
    }
}
