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
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('tenants')) {
            return;
        }

        $guard = (string) config('auth.defaults.guard', 'web');
        $permissions = [
            VehicleRentalAuthorizationService::VIEW => 'View Vehicle Rental operational records.',
            VehicleRentalAuthorizationService::VIEW_FINANCIAL => 'View Vehicle Rental financial records.',
            VehicleRentalAuthorizationService::VIEW_PROFITABILITY => 'View Vehicle Rental profitability.',
            VehicleRentalAuthorizationService::MANAGE_RESERVATIONS => 'Create and progress rental reservations.',
            VehicleRentalAuthorizationService::MANAGE_AGREEMENTS => 'Create and progress customer and owner rental agreements.',
            VehicleRentalAuthorizationService::MANAGE_RATES => 'Create and activate immutable rental rate versions.',
            VehicleRentalAuthorizationService::MANAGE_ALLOCATIONS => 'Allocate vehicles and drivers.',
            VehicleRentalAuthorizationService::MANAGE_CUSTODY => 'Record and confirm rental custody handovers and returns.',
            VehicleRentalAuthorizationService::MANAGE_REPLACEMENTS => 'Replace active rental vehicles atomically.',
            VehicleRentalAuthorizationService::RECORD_USAGE => 'Create and submit rental running charts.',
            VehicleRentalAuthorizationService::APPROVE_USAGE => 'Approve, reject, or reverse rental running charts.',
            VehicleRentalAuthorizationService::RECORD_EXPENSES => 'Create and submit rental expenses.',
            VehicleRentalAuthorizationService::APPROVE_EXPENSES => 'Approve, reject, or reverse rental expenses.',
            VehicleRentalAuthorizationService::CALCULATE => 'Generate and submit rental revenue and owner cost calculations.',
            VehicleRentalAuthorizationService::APPROVE_CALCULATIONS => 'Approve or reverse rental calculations.',
            VehicleRentalAuthorizationService::CREATE_FINANCIAL_DOCUMENTS => 'Create rental invoices, payables, and finance installment payables.',
            VehicleRentalAuthorizationService::MANAGE_DEPOSITS => 'Receive, apply, refund, forfeit, and reverse rental deposits.',
            VehicleRentalAuthorizationService::MANAGE_FINANCE_AGREEMENTS => 'Manage vehicle lease and finance agreements.',
        ];

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach ($permissions as $name => $description) {
                DB::table('permissions')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard],
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
}
