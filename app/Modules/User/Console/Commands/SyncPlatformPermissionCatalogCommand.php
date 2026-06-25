<?php

declare(strict_types=1);

namespace Modules\User\Console\Commands;

use Illuminate\Console\Command;
use Modules\User\Services\Platform\PlatformPermissionCatalogSynchronizer;

final class SyncPlatformPermissionCatalogCommand extends Command
{
    protected $signature = 'platform:permissions:sync';
    protected $description = 'Synchronize the authoritative platform permission catalogue.';

    public function handle(PlatformPermissionCatalogSynchronizer $synchronizer): int
    {
        $result = $synchronizer->synchronize();
        $this->info(sprintf(
            'Platform permissions synchronized: %d total, %d created, %d updated, %d deactivated.',
            $result['total'],
            $result['created'],
            $result['updated'],
            $result['deactivated'],
        ));

        return self::SUCCESS;
    }
}
