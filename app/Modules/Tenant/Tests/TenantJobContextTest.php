<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Modules\Core\Support\TenantExecutionContext;
use Modules\Tenant\Queue\RestoreTenantJobContext;
use Modules\Tenant\Queue\TenantAwareJobInterface;
use Modules\Tenant\Queue\TenantJobContext;
use PHPUnit\Framework\TestCase;

final class TenantJobContextTest extends TestCase
{
    public function test_queue_middleware_restores_and_clears_tenant_context(): void
    {
        $execution = new TenantExecutionContext();
        $middleware = new RestoreTenantJobContext($execution);
        $job = new class implements TenantAwareJobInterface {
            public function tenantJobContext(): TenantJobContext
            {
                return new TenantJobContext(42);
            }
        };

        $observed = $middleware->handle($job, static fn (): ?int => $execution->tenantId());

        self::assertSame(42, $observed);
        self::assertNull($execution->tenantId());
        self::assertFalse($execution->isActive());
    }
}
