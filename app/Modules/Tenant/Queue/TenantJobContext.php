<?php

declare(strict_types=1);

namespace Modules\Tenant\Queue;

use InvalidArgumentException;

final readonly class TenantJobContext
{
    public function __construct(public int $tenantId)
    {
        if ($this->tenantId < 1) {
            throw new InvalidArgumentException('A tenant job context requires a positive tenant identifier.');
        }
    }

    /** @return array{tenant_id:int} */
    public function toArray(): array
    {
        return ['tenant_id' => $this->tenantId];
    }
}
