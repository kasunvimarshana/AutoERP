<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class VehicleRentalSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        $tenant = $this->defaultTenant();
        if ($tenant === null) {
            return;
        }
        $guard = (string) config('auth.defaults.guard', 'web');
        foreach ([
            VehicleRentalAuthorizationService::MANAGE_RESERVATIONS => 'Create, edit, and progress rental reservations.',
            VehicleRentalAuthorizationService::MANAGE_AGREEMENTS => 'Create and progress rental agreements.',
            VehicleRentalAuthorizationService::MANAGE_ALLOCATIONS => 'Allocate and replace rental vehicles.',
            VehicleRentalAuthorizationService::RECORD_INSPECTIONS => 'Record rental pickup and return inspections.',
            VehicleRentalAuthorizationService::MANAGE_LINKS => 'Manage linked inbound and outbound rental allocations.',
            VehicleRentalAuthorizationService::APPROVE_LINKS => 'Approve linked inbound and outbound rental allocations.',
            VehicleRentalAuthorizationService::RECORD_USAGE => 'Create and submit rental running charts and events.',
            VehicleRentalAuthorizationService::APPROVE_USAGE => 'Approve submitted rental running charts.',
            VehicleRentalAuthorizationService::OVERRIDE_MILEAGE => 'Approve documented rental mileage-chain exceptions.',
            VehicleRentalAuthorizationService::CLASSIFY_HOLIDAY => 'Classify documented rental usage as public-holiday work.',
            VehicleRentalAuthorizationService::RECORD_EXPENSES => 'Create and submit rental expenses.',
            VehicleRentalAuthorizationService::APPROVE_EXPENSES => 'Approve rental expenses.',
            VehicleRentalAuthorizationService::GENERATE_CHARGES => 'Generate rental revenue and cost calculations.',
            VehicleRentalAuthorizationService::APPROVE_CHARGES => 'Approve calculated rental charges and costs.',
            VehicleRentalAuthorizationService::CREATE_FINANCIAL_DOCUMENTS => 'Create rental invoices, payables, and payments.',
        ] as $name => $description) {
            DB::table('permissions')->updateOrInsert(
                ['tenant_id' => $tenant->getKey(), 'name' => $name, 'guard_name' => $guard],
                [
                    'organization_unit_id' => null,
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
