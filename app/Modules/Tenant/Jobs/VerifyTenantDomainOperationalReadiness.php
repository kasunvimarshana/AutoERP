<?php

declare(strict_types=1);

namespace Modules\Tenant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Tenant\Constants\TenantDomainOperationalVerificationOutcome;
use Modules\Tenant\Queue\TenantAwareJobInterface;
use Modules\Tenant\Queue\TenantJobContext;
use Modules\Tenant\Queue\RestoreTenantJobContext;
use Modules\Tenant\Services\Domains\TenantDomainOperationalVerificationService;
use RuntimeException;

final class VerifyTenantDomainOperationalReadiness implements ShouldQueue, TenantAwareJobInterface
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(
        public readonly int $tenantId,
        public readonly int $domainId,
    ) {}

    /** @return list<object> */
    public function middleware(): array
    {
        return [app(RestoreTenantJobContext::class)];
    }

    public function tenantJobContext(): TenantJobContext
    {
        return new TenantJobContext($this->tenantId);
    }

    public function handle(TenantDomainOperationalVerificationService $service): void
    {
        $outcome = $service->execute($this->tenantId, $this->domainId);
        if ($outcome === TenantDomainOperationalVerificationOutcome::RETRY) {
            throw new RuntimeException('Tenant domain operational verification is pending.');
        }
    }
}
