<?php

declare(strict_types=1);

namespace Modules\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerVehicle;
use Modules\Vehicle\Models\Vehicle;

final class CustomerVehicleSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('customer_vehicles')) {
            return;
        }

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $tenantId = (int) $tenant->getKey();
            $customer = Customer::query()
                ->where('tenant_id', $tenantId)
                ->where('customer_number', 'CUS-000001')
                ->first();
            $vehicle = Vehicle::query()
                ->where('tenant_id', $tenantId)
                ->where('vehicle_number', 'VEH-000001')
                ->first();

            if ($customer === null || $vehicle === null) {
                return;
            }

            CustomerVehicle::query()->firstOrNew([
                'vehicle_id' => $vehicle->getKey(),
                'customer_id' => $customer->getKey(),
                'active_guard' => 1,
            ])->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnit?->getKey(),
                'relationship_type' => 'customer_owned',
                'started_at' => '2026-01-01 00:00:00',
                'ended_at' => null,
                'is_current' => true,
                'current_guard' => 1,
                'notes' => 'Default customer ownership.',
            ])->save();
        }, 3);
    }
}
