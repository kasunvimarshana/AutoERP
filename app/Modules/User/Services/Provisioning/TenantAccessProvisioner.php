<?php

declare(strict_types=1);

namespace Modules\User\Services\Provisioning;

use Illuminate\Support\Facades\DB;
use Modules\Tenant\Services\Contracts\TenantAccessProvisionerInterface;
use Modules\User\Constants\UserPermission;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\User\Models\PermissionModel;
use Modules\User\Models\RoleModel;

final class TenantAccessProvisioner implements TenantAccessProvisionerInterface
{
    private const GUARD_NAME = 'api';

    public function __construct(
        private readonly PermissionDefinitionRegistryInterface $permissionDefinitions,
    ) {}

    public function provision(int $tenantId): array
    {
        return DB::transaction(function () use ($tenantId): array {
            $definitions = $this->permissionDefinitions->all();

            foreach ($definitions as $name => $definition) {
                PermissionModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'name' => $name,
                        'guard_name' => self::GUARD_NAME,
                    ],
                    [
                        'organization_unit_id' => null,
                        'module' => $definition['module'],
                        'description' => $definition['description'],
                        'metadata' => ['system_defined' => true],
                        'row_version' => 1,
                    ],
                );
            }

            $role = RoleModel::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => UserPermission::SUPER_ADMIN_ROLE,
                    'guard_name' => self::GUARD_NAME,
                ],
                [
                    'organization_unit_id' => null,
                    'description' => 'Protected tenant super administrator role.',
                    'metadata' => ['system_defined' => true, 'protected' => true],
                    'row_version' => 1,
                ],
            );

            return [
                'role_id' => (int) $role->getKey(),
                'permission_count' => count($definitions),
            ];
        }, 3);
    }

    public function isReady(int $tenantId): bool
    {
        $expected = count($this->permissionDefinitions->all());

        return RoleModel::query()
            ->where('tenant_id', $tenantId)
            ->where('name', UserPermission::SUPER_ADMIN_ROLE)
            ->where('guard_name', self::GUARD_NAME)
            ->whereNull('deleted_at')
            ->exists()
            && PermissionModel::query()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->count() >= $expected;
    }
}
