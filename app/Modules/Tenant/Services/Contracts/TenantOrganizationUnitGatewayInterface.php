<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

use Modules\Core\DTOs\DataRecord;

interface TenantOrganizationUnitGatewayInterface
{
    /** @param array<string, mixed> $payload */
    public function provisionRoot(int $tenantId, array $payload): DataRecord;

    public function findRoot(int $tenantId): ?DataRecord;
}
