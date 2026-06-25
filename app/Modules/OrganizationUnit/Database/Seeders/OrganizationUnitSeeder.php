<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;

final class OrganizationUnitSeeder extends Seeder
{
    use ResolvesSeedContext;

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
            $unit = OrganizationUnitModel::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'name' => 'Head Office'],
                [
                    'type_id' => $type?->getKey(),
                    'parent_id' => null,
                    'code' => $code,
                    'depth' => 0,
                    'is_active' => true,
                    'description' => 'Default organization unit.',
                    'row_version' => 1,
                    'metadata' => json_encode(
                        ['seed_source' => 'organization_unit_module'],
                        JSON_THROW_ON_ERROR,
                    ),
                ],
            );

            $unit->forceFill(['path' => '/'.$unit->getKey().'/'])->save();
        }, 3);
    }
}
