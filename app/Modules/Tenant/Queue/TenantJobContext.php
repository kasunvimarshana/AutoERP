<?php

declare(strict_types=1);

namespace Modules\Tenant\Queue;

use InvalidArgumentException;

final readonly class TenantJobContext
{
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId = null,
        public ?string $correlationId = null,
    ) {
        if ($this->tenantId < 1) {
            throw new InvalidArgumentException('A tenant job context requires a positive tenant identifier.');
        }
        if ($this->organizationUnitId !== null && $this->organizationUnitId < 1) {
            throw new InvalidArgumentException('Organization unit identifier must be positive when provided.');
        }
    }

    /** @return array{tenant_id:int,organization_unit_id:?int,correlation_id:?string} */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'organization_unit_id' => $this->organizationUnitId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
