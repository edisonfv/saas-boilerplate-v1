<?php

namespace App\Console\Commands;

use App\Services\CentralPermissionSyncer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('central:sync-permissions')]
#[Description('Rebuild the central ACL catalog from Modules\Central\Permissions\CentralPermissions and grant it to super-admin.')]
class SyncCentralPermissions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CentralPermissionSyncer $syncer): int
    {
        $syncer->sync();

        $this->info('Central permission catalog synced.');

        return self::SUCCESS;
    }
}
