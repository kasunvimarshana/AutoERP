<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantStatus;

final class SuspendTenantService
{
    public function __construct(private readonly TenantLifecycleService $lifecycle) {}

    public function execute(
        int|string $id,
        int $expectedVersion,
        ?string $reason = null,
    ): Result {
        return $this->lifecycle->transition(
            $id,
            $expectedVersion,
            TenantStatus::SUSPENDED,
            $reason,
        );
    }
}
