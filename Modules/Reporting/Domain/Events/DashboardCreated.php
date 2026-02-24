<?php

namespace Modules\Reporting\Domain\Events;

class DashboardCreated
{
    public function __construct(
        public readonly string $dashboardId,
        public readonly string $tenantId,
        public readonly string $name,
    ) {}
}
