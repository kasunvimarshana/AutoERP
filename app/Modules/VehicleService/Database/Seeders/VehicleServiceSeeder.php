<?php

declare(strict_types=1);

namespace Modules\VehicleService\Database\Seeders;

use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleService\Services\VehicleServiceJobTypeService;

final class VehicleServiceSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('vehicle_service_job_types')) {
            return;
        }

        $tenant = $this->defaultTenant();
        if ($tenant === null) {
            return;
        }

        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        $tenantId = (int) $tenant->getKey();
        $organizationUnitId = $organizationUnit ? (int) $organizationUnit->getKey() : null;

        app(VehicleServiceJobTypeService::class)->ensureDefaults($tenantId, $organizationUnitId);
    }
}
