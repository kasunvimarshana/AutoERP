<?php

declare(strict_types=1);

namespace Modules\Tenant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Tenant\Constants\TenantDomainOwnershipVerificationOutcome;
use Modules\Tenant\Queue\TenantAwareJobInterface;
use Modules\Tenant\Queue\TenantJobContext;
use Modules\Tenant\Queue\RestoreTenantJobContext;
use Modules\Tenant\Services\Domains\TenantDomainOwnershipVerificationService;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use RuntimeException;
use Throwable;

final class VerifyTenantDomainOwnership implements ShouldQueue, TenantAwareJobInterface
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
        public readonly string $challengeHash,
        public readonly ?int $requestedBy,
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

    public function handle(TenantDomainOwnershipVerificationService $service): void
    {
        $outcome = $service->execute($this->tenantId, $this->domainId, $this->challengeHash, $this->requestedBy);
        if ($outcome === TenantDomainOwnershipVerificationOutcome::RETRY) {
            throw new RuntimeException('Tenant domain ownership verification is pending.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(TenantExecutionContextInterface::class)->runForTenant(
            $this->tenantId,
            fn (): mixed => app(TenantDomainOwnershipVerificationService::class)->markFailed(
                $this->tenantId,
                $this->domainId,
                $this->challengeHash,
            ),
        );
    }
}
