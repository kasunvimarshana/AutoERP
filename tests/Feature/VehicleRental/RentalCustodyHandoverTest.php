<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalCustodyStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalCustodyEvent;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalAllocationService;
use Modules\VehicleRental\Services\RentalCustodyService;
use Tests\TestCase;

final class RentalCustodyHandoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_vehicle_handover_and_confirmation_lifecycle(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $vehicleId = $this->createVehicle($tenantId, null, '12000.000000');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $customerId, $currencyId, $vehicleId): void {
            $agreement = app(RentalAgreementService::class)->create([
                'agreement_number' => 'RA-HANDOVER-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-05',
                'starts_at' => '2026-07-05 08:00:00',
                'ends_at' => '2026-08-05 08:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'self_drive',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-05 08:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '0.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '1000.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                        'is_taxable' => true,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null);

            // Activate agreement
            $agreement->update(['status' => 'active']);

            $allocation = app(RentalAllocationService::class)->create(
                $agreement,
                [
                    'vehicle_id' => $vehicleId,
                    'vehicle_source_type' => 'company_owned',
                    'allocated_from' => '2026-07-05 08:00:00',
                    'allocated_to' => '2026-08-05 08:00:00',
                    'start_odometer' => '12000.000000',
                ],
                null,
            );

            self::assertSame(RentalAllocationStatus::Planned, $allocation->status);

            // Create handover custody event (company_to_customer)
            $custodyService = app(RentalCustodyService::class);
            $event = $custodyService->create(
                $allocation,
                [
                    'event_type' => RentalCustodyEventType::CompanyToCustomer->value,
                    'occurred_at' => '2026-07-05 08:30:00',
                    'odometer' => '12000.000000',
                    'fuel_level_percent' => '100.00',
                    'location' => 'Main Depot',
                ],
                null,
            );

            self::assertSame(RentalCustodyStatus::Draft, $event->status);
            self::assertSame(1, (int) $event->row_version);

            // Confirm custody event
            $confirmedEvent = $custodyService->confirm($event, 1, null);

            self::assertSame(RentalCustodyStatus::Confirmed, $confirmedEvent->status);
            self::assertSame(2, (int) $confirmedEvent->row_version);

            // Check allocation is now active
            $allocation->refresh();
            self::assertSame(RentalAllocationStatus::Active, $allocation->status);

            // Check vehicle is now rented
            $vehicle = Vehicle::query()->findOrFail($vehicleId);
            self::assertSame(VehicleStatus::Rented, $vehicle->status);
        });
    }

    public function test_owner_supplied_vehicle_requires_receipt_before_customer_handover(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $supplierId = $this->createSupplier($tenantId);
        $vehicleId = $this->createVehicle($tenantId, null, '25000.000000');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $customerId, $supplierId, $currencyId, $vehicleId): void {
            $agreementService = app(RentalAgreementService::class);
            $allocationService = app(RentalAllocationService::class);
            $custodyService = app(RentalCustodyService::class);

            // 1. Create owner supply agreement
            $ownerAgreement = $agreementService->create([
                'agreement_number' => 'RA-OWNER-001',
                'agreement_kind' => 'owner_supply',
                'supplier_id' => $supplierId,
                'agreement_date' => '2026-07-01',
                'starts_at' => '2026-07-01 08:00:00',
                'ends_at' => '2026-12-31 18:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'self_drive',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-01 08:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '0.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '800.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                        'is_taxable' => false,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null);
            $ownerAgreement->update(['status' => 'active']);

            // 2. Create customer rental agreement
            $customerAgreement = $agreementService->create([
                'agreement_number' => 'RA-CUST-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-05',
                'starts_at' => '2026-07-05 08:00:00',
                'ends_at' => '2026-08-05 08:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'self_drive',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-05 08:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '0.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '1200.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                        'is_taxable' => true,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null);
            $customerAgreement->update(['status' => 'active']);

            // 3. Create owner supply allocation (source allocation)
            $ownershipId = $this->createOwnership($tenantId, $vehicleId, $supplierId);
            $ownerAllocation = $allocationService->create($ownerAgreement, [
                'vehicle_id' => $vehicleId,
                'vehicle_source_type' => 'owner_supplied',
                'vehicle_ownership_id' => $ownershipId,
                'allocated_from' => '2026-07-01 08:00:00',
                'allocated_to' => '2026-12-31 18:00:00',
                'start_odometer' => '25000.000000',
            ], null);

            // 4. Create customer allocation sourcing owner-supplied vehicle
            $customerAllocation = $allocationService->create($customerAgreement, [
                'vehicle_id' => $vehicleId,
                'vehicle_source_type' => 'owner_supplied',
                'source_allocation_id' => $ownerAllocation->id,
                'allocated_from' => '2026-07-05 08:00:00',
                'allocated_to' => '2026-08-05 08:00:00',
                'start_odometer' => '25000.000000',
            ], null);

            self::assertSame(RentalAllocationStatus::Planned, $ownerAllocation->status);
            self::assertSame(RentalAllocationStatus::Planned, $customerAllocation->status);

            // 5. Attempting to create customer handover before vehicle received from owner must fail
            try {
                $custodyService->create($customerAllocation, [
                    'event_type' => RentalCustodyEventType::CompanyToCustomer->value,
                    'occurred_at' => '2026-07-05 09:00:00',
                    'odometer' => '25000.000000',
                ], null);
                self::fail('Expected ValidationException when creating customer handover before owner receipt.');
            } catch (ValidationException $e) {
                self::assertArrayHasKey('event_type', $e->errors());
                self::assertStringContainsString('Owner-supplied vehicle must be received', $e->errors()['event_type'][0]);
            }

            // 6. Receive vehicle from owner on ownerAllocation
            $ownerEvent = $custodyService->create($ownerAllocation, [
                'event_type' => RentalCustodyEventType::OwnerToCompany->value,
                'occurred_at' => '2026-07-01 09:00:00',
                'odometer' => '25000.000000',
            ], null);

            // Activating ownerAllocation MUST NOT conflict with the planned downstream customer allocation
            $confirmedOwnerEvent = $custodyService->confirm($ownerEvent, 1, null);
            self::assertSame(RentalCustodyStatus::Confirmed, $confirmedOwnerEvent->status);
            $ownerAllocation->refresh();
            self::assertSame(RentalAllocationStatus::Active, $ownerAllocation->status);

            // 7. Customer handover can now be created and confirmed
            $customerEvent = $custodyService->create($customerAllocation, [
                'event_type' => RentalCustodyEventType::CompanyToCustomer->value,
                'occurred_at' => '2026-07-05 09:00:00',
                'odometer' => '25000.000000',
            ], null);
            $confirmedCustomerEvent = $custodyService->confirm($customerEvent, 1, null);
            self::assertSame(RentalCustodyStatus::Confirmed, $confirmedCustomerEvent->status);

            $customerAllocation->refresh();
            self::assertSame(RentalAllocationStatus::Active, $customerAllocation->status);

            $vehicle = Vehicle::query()->findOrFail($vehicleId);
            self::assertSame(VehicleStatus::Rented, $vehicle->status);
        });
    }

    private function createSupplier(int $tenantId): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'supplier_number' => 'SUP-VR-H01',
            'code' => 'SUP-VRH',
            'name' => 'Owner Supplier',
            'legal_name' => 'Owner Supplier Legal',
            'display_name' => 'Owner Supplier',
            'supplier_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOwnership(int $tenantId, int $vehicleId, int $supplierId): int
    {
        return (int) DB::table('vehicle_ownerships')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_id' => $vehicleId,
            'owner_type' => 'supplier',
            'owner_id' => $supplierId,
            'owner_key' => 'supplier:'.$supplierId,
            'owner_code_snapshot' => 'SUP-VRH',
            'owner_name_snapshot' => 'Owner Supplier',
            'ownership_type' => 'supplier_owned',
            'started_at' => '2026-01-01 00:00:00',
            'ended_at' => null,
            'is_current' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCurrency(): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => 'VRH',
            'name' => 'Vehicle Rental Handover Currency',
            'symbol' => 'VRH',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTenant(int $currencyId): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'VR-HAND',
            'name' => 'Vehicle Rental Handover Tenant',
            'slug' => 'vehicle-rental-handover-tenant',
            'base_currency_id' => $currencyId,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCustomer(int $tenantId, int $currencyId): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-VR-H01',
            'code' => 'CUS-VRH',
            'name' => 'Handover Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createVehicle(int $tenantId, ?int $orgUnitId, string $odometer): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgUnitId,
            'vehicle_number' => 'VEH-HND-01',
            'odometer_reading' => $odometer,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
