<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Listeners;

use Modules\Tenant\Application\Events\TenantStatusChanged;

final class RecordTenantLifecycleAuditListener
{
    public function handle(TenantStatusChanged $event): void
    {
        // Reserved integration point for centralized audit trails.
    }
}
