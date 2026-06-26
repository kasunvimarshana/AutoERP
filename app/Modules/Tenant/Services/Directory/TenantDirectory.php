<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Directory;

use Modules\Core\Contracts\TenantDirectoryInterface;
use Modules\Tenant\Models\TenantModel;

final class TenantDirectory implements TenantDirectoryInterface
{
    public function summary(int $tenantId): ?array
    {
        if ($tenantId < 1) {
            return null;
        }

        $tenant = TenantModel::query()->whereKey($tenantId)->first(['id', 'code', 'name', 'status']);
        if (! $tenant instanceof TenantModel) {
            return null;
        }

        return [
            'id' => (int) $tenant->getKey(),
            'code' => (string) $tenant->getAttribute('code'),
            'name' => (string) $tenant->getAttribute('name'),
            'status' => (string) $tenant->getAttribute('status'),
        ];
    }
}
