<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class VehicleRentalSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        $this->seedPermissions();
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('tenants')) {
            return;
        }

        $guard = (string) config('auth.defaults.guard', 'web');
        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach (VehicleRentalAuthorizationService::descriptions() as $name => $description) {
                DB::table('permissions')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard],
                    [
                        'module' => 'VehicleRental',
                        'description' => $description,
                        'row_version' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }
}
