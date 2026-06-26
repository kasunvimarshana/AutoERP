<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Authentication;

use Modules\Core\Contracts\TenantAuthenticationDirectoryInterface;
use Modules\Tenant\Models\TenantModel;
use Modules\Tenant\Services\TenantEntitlementService;

final readonly class TenantAuthenticationDirectory implements TenantAuthenticationDirectoryInterface
{
    public function __construct(private TenantEntitlementService $entitlements) {}

    public function findActive(int $tenantId): ?array
    {
        $tenant = TenantModel::query()->whereKey($tenantId)->where('status', 'active')->first();
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

    public function enabledModules(int $tenantId): array
    {
        return $this->entitlements->enabledModules($tenantId);
    }
}
