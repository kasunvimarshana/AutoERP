<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Services\Storage\TenantStorageCleanupService;

final class TenantStorageCleanupCommand extends Command
{
    protected $signature = 'tenant:storage:cleanup {--limit= : Maximum cleanup jobs to process}';

    protected $description = 'Delete tenant files queued after a storage cleanup failure.';

    public function handle(TenantStorageCleanupService $service): int
    {
        $limit = $this->option('limit');
        $summary = $service->process(is_numeric($limit) ? (int) $limit : null);

        $this->info(sprintf(
            'Checked %d; completed %d; failed %d; dead %d; recovered %d.',
            $summary['checked'],
            $summary['completed'],
            $summary['failed'],
            $summary['dead'],
            $summary['recovered'],
        ));

        return ($summary['failed'] + $summary['dead']) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
