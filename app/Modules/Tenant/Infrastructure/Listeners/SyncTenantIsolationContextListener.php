<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Listeners;

use Modules\Tenant\Application\Events\TenantCreated;

final class SyncTenantIsolationContextListener
{
    public function handle(TenantCreated $event): void
    {
        // Reserved integration point for provisioning tenant-scoped resources.
    }
}
