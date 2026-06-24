<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Provisioning;

use Illuminate\Support\Facades\DB;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;

final class TenantOrganizationProvisioner implements TenantOrganizationProvisionerInterface
{
    private const ROOT_TYPE_NAME = 'Company';

    public function provision(int $tenantId, string $tenantCode, string $tenantName): array
    {
        return DB::transaction(function () use ($tenantId, $tenantCode, $tenantName): array {
            $type = OrganizationUnitTypeModel::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => self::ROOT_TYPE_NAME,
                ],
                [
                    'level' => 0,
                    'is_active' => true,
                    'metadata' => ['system' => true, 'purpose' => 'tenant_root'],
                    'row_version' => 1,
                ],
            );

            $root = OrganizationUnitModel::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'parent_id' => null,
                    'code' => strtoupper(trim($tenantCode)),
                ],
                [
                    'type_id' => (int) $type->getKey(),
                    'name' => trim($tenantName),
                    'path' => '/'.strtolower(trim($tenantCode)),
                    'depth' => 0,
                    'is_active' => true,
                    'description' => 'Root organization unit created by tenant onboarding.',
                    '_lft' => 1,
                    '_rgt' => 2,
                    'metadata' => ['system' => true, 'purpose' => 'tenant_root'],
                    'row_version' => 1,
                ],
            );

            if (! (bool) $root->getAttribute('is_active')) {
                $root->forceFill([
                    'is_active' => true,
                    'row_version' => (int) $root->getAttribute('row_version') + 1,
                ])->save();
            }

            return ['organization_unit_id' => (int) $root->getKey()];
        }, 3);
    }

    public function isReady(int $tenantId): bool
    {
        return OrganizationUnitModel::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->exists();
    }
}
