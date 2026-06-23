<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Services\Domains\RevalidateTenantDomainsService;

final class TenantDomainRevalidateCommand extends Command
{
    protected $signature = 'tenant:domains:revalidate {--limit= : Maximum domains to check}';

    protected $description = 'Revalidate due tenant custom-domain DNS ownership proofs.';

    public function handle(RevalidateTenantDomainsService $service): int
    {
        $limit = $this->option('limit');
        $summary = $service->execute(is_numeric($limit) ? (int) $limit : null);

        $this->info(sprintf(
            'Checked %d; verified %d; failed %d; disabled %d.',
            $summary['checked'],
            $summary['verified'],
            $summary['failed'],
            $summary['disabled'],
        ));

        return self::SUCCESS;
    }
}
