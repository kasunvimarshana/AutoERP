<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RoleModel;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = TenantModel::create([
            'id' => 1,
            'name' => 'Default',
            'slug' => 'default'
        ]);

        $ou = OrganizationUnitModel::create([
            'id' => 1,
            'tenant_id' => $tenant->id,
            'name' => 'Default'
        ]);

        collect(['super-admin', 'admin', 'guest'])->each(function ($v) use ($tenant, $ou) {
            RoleModel::create([
                'tenant_id' => $tenant->id,
                'organization_unit_id' => $ou->id,
                'name' => $v
            ]);
        });
    }
}
