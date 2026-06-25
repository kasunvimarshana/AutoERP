<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;
use Modules\OrganizationUnit\Services\OrganizationUnits\OrganizationHierarchyService;

final class OrganizationUnitSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function __construct(private readonly OrganizationHierarchyService $hierarchy) {}

    public function run(): void
    {
        if (! Schema::hasTable('organization_units')) {
            return;
        }

        $tenant = $this->defaultTenant();
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant): void {
            $type = null;
            if (Schema::hasTable('organization_unit_types')) {
                $type = OrganizationUnitTypeModel::query()->updateOrCreate(
                    ['tenant_id' => $tenant->getKey(), 'name' => 'Head Office'],
                    [
                        'level' => 0,
                        'is_active' => true,
                        'row_version' => 1,
                        'metadata' => json_encode(
                            ['seed_source' => 'organization_unit_module'],
                            JSON_THROW_ON_ERROR,
                        ),
                    ],
                );
            }

            $code = $this->defaultOrganizationUnitCode();
            if ($type === null) {
                return;
            }

            $this->hierarchy->createRoot(
                tenantId: (int) $tenant->getKey(),
                typeId: (int) $type->getKey(),
                code: $code,
                name: 'Head Office',
                description: 'Default organization unit.',
                metadata: ['seed_source' => 'organization_unit_module'],
            );
        }, 3);
    }
}
