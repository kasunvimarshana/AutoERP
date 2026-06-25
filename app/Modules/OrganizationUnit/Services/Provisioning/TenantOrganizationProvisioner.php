<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Provisioning;

use Illuminate\Support\Facades\DB;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;
use Modules\OrganizationUnit\Services\OrganizationUnits\OrganizationHierarchyService;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;

final class TenantOrganizationProvisioner implements TenantOrganizationProvisionerInterface
{
    private const ROOT_TYPE_NAME = 'Company';

    public function __construct(private readonly OrganizationHierarchyService $hierarchy) {}

    public function provision(int $tenantId, string $tenantCode, string $tenantName): array
    {
        return DB::transaction(function () use ($tenantId, $tenantCode, $tenantName): array {
            $type = OrganizationUnitTypeModel::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('name', self::ROOT_TYPE_NAME)
                ->lockForUpdate()
                ->first();
            if ($type instanceof OrganizationUnitTypeModel) {
                $metadata = is_array($type->getAttribute('metadata'))
                    ? $type->getAttribute('metadata')
                    : [];
                $attributes = [
                    'level' => 0,
                    'is_active' => true,
                    'metadata' => [
                        ...$metadata,
                        'system' => true,
                        'purpose' => 'tenant_root',
                    ],
                    'deleted_at' => null,
                ];
                $dirty = false;
                foreach ($attributes as $attribute => $value) {
                    if ($type->getAttribute($attribute) !== $value) {
                        $dirty = true;
                        break;
                    }
                }
                if ($dirty) {
                    $type->forceFill([
                        ...$attributes,
                        'row_version' => max(1, (int) $type->getAttribute('row_version')) + 1,
                    ])->save();
                }
            } else {
                $type = OrganizationUnitTypeModel::query()->create([
                    'tenant_id' => $tenantId,
                    'name' => self::ROOT_TYPE_NAME,
                    'level' => 0,
                    'is_active' => true,
                    'metadata' => ['system' => true, 'purpose' => 'tenant_root'],
                    'row_version' => 1,
                ]);
            }

            $root = $this->hierarchy->createRoot(
                tenantId: $tenantId,
                typeId: (int) $type->getKey(),
                code: $tenantCode,
                name: $tenantName,
                description: 'Protected root organization unit created by tenant onboarding.',
                metadata: ['system' => true, 'purpose' => 'tenant_root'],
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
