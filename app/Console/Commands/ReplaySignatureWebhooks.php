<?php

namespace App\Console\Commands;

use App\Models\SignatureWebhookEvent;
use App\Services\Signatures\SignatureStatusSynchronizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('signatures:replay-webhooks {--limit=200 : Max events to process}')]
#[Description('Reprocess signature provider webhooks that failed (e.g. arrived before their sale was indexed).')]
class ReplaySignatureWebhooks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SignatureStatusSynchronizer $synchronizer): int
    {
        $events = SignatureWebhookEvent::query()
            ->whereNull('processed_at')
            ->oldest()
            ->limit((int) $this->option('limit'))
            ->get();

        $processed = $events->filter(fn (SignatureWebhookEvent $event) => $synchronizer->process($event))->count();

        $this->info("Processed {$processed} of {$events->count()} pending webhook(s).");

        return self::SUCCESS;
    }
}
