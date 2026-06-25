<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Provisioning;

use Illuminate\Support\Facades\DB;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;
use Modules\OrganizationUnit\Services\OrganizationUnits\OrganizationHierarchyService;
use Modules\OrganizationUnit\Support\OrganizationUnitNameKey;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;

final class TenantOrganizationProvisioner implements TenantOrganizationProvisionerInterface
{
    private const ROOT_TYPE_NAME = 'Company';

    public function __construct(private readonly OrganizationHierarchyService $hierarchy) {}

    public function provision(int $tenantId, string $tenantCode, string $tenantName): array
    {
        return DB::transaction(function () use ($tenantId, $tenantCode, $tenantName): array {
            $type = OrganizationUnitTypeModel::query()
                ->where('tenant_id', $tenantId)
                ->where('name_key', OrganizationUnitNameKey::from(self::ROOT_TYPE_NAME))
                ->lockForUpdate()
                ->first();

            if ($type instanceof OrganizationUnitTypeModel) {
                if ((int) $type->getAttribute('level') !== 0 || ! (bool) $type->getAttribute('is_active')) {
                    $type->forceFill([
                        'level' => 0,
                        'is_active' => true,
                        'row_version' => max(1, (int) $type->getAttribute('row_version')) + 1,
                    ])->save();
                }
            } else {
                $type = new OrganizationUnitTypeModel();
                $type->forceFill([
                    'tenant_id' => $tenantId,
                    'name' => self::ROOT_TYPE_NAME,
                    'name_key' => OrganizationUnitNameKey::from(self::ROOT_TYPE_NAME),
                    'level' => 0,
                    'is_active' => true,
                    'row_version' => 1,
                ])->save();
            }

            $root = $this->hierarchy->createRoot(
                tenantId: $tenantId,
                typeId: (int) $type->getKey(),
                code: $tenantCode,
                name: $tenantName,
                description: 'Protected root organization unit created by tenant onboarding.',
            );

            return ['organization_unit_id' => (int) $root->getKey()];
        }, 3);
    }

    public function isReady(int $tenantId, int $organizationUnitId, bool $lockForUpdate = false): bool
    {
        return $this->hierarchy->rootIsReady($tenantId, $organizationUnitId, $lockForUpdate);
    }

    public function protectedRootId(int $tenantId, bool $lockForUpdate = false): ?int
    {
        return $this->hierarchy->protectedRootId($tenantId, $lockForUpdate);
    }
}
