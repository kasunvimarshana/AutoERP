<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Services\ExpireTenantsService;

final class TenantExpireCommand extends Command
{
    protected $signature = 'tenant:expire {--limit= : Maximum tenants to process}';

    protected $description = 'Deactivate active tenants whose subscription or trial has expired.';

    public function handle(ExpireTenantsService $service): int
    {
        $limit = $this->option('limit');
        $summary = $service->execute(is_numeric($limit) ? (int) $limit : null);
        $this->info(sprintf(
            'Checked %d; expired %d; conflicts %d.',
            $summary['checked'],
            $summary['expired'],
            $summary['conflicts'],
        ));

        return self::SUCCESS;
    }
}
