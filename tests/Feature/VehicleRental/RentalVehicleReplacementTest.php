<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalReplacementStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Models\RentalVehicleReplacement;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalAllocationService;
use Modules\VehicleRental\Services\RentalCustodyService;
use Modules\VehicleRental\Services\RentalReplacementService;
use Tests\TestCase;

final class RentalVehicleReplacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_replacement_lifecycle_and_traceability(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $oldVehicleId = $this->createVehicle($tenantId, 'CBC-9180', '137000.000000');
        $newVehicleId = $this->createVehicle($tenantId, 'CAP-1302', '190000.000000');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $customerId, $currencyId, $oldVehicleId, $newVehicleId): void {
            $agreement = app(RentalAgreementService::class)->create([
                'agreement_number' => 'RA-REPL-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-01',
                'starts_at' => '2026-07-01 08:00:00',
                'ends_at' => '2026-07-31 18:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-01 08:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '3000.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '150000.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null);

            $agreement->update(['status' => 'active']);

            $allocationService = app(RentalAllocationService::class);
            $custodyService = app(RentalCustodyService::class);
            $replacementService = app(RentalReplacementService::class);

            // 1. Initial allocation for CBC-9180
            $oldAllocation = $allocationService->create($agreement, [
                'vehicle_id' => $oldVehicleId,
                'vehicle_source_type' => 'company_owned',
                'allocated_from' => '2026-07-01 08:00:00',
                'allocated_to' => '2026-07-31 18:00:00',
                'start_odometer' => '137130.000000',
            ], null);

            // Handover to customer to activate
            $handover = $custodyService->create($oldAllocation, [
                'event_type' => RentalCustodyEventType::CompanyToCustomer->value,
                'occurred_at' => '2026-07-01 08:00:00',
                'odometer' => '137130.000000',
                'from_role' => 'company',
                'to_role' => 'customer',
            ], null);
            $custodyService->confirm($handover, (int) $handover->row_version, null);

            $oldAllocation->refresh();
            self::assertSame(RentalAllocationStatus::Active, $oldAllocation->status);

            // 2. Perform replacement with CAP-1302 due to breakdown
            $replacement = $replacementService->replace($oldAllocation, [
                'new_vehicle_id' => $newVehicleId,
                'vehicle_source_type' => 'company_owned',
                'replacement_at' => '2026-07-06 14:00:00',
                'reason_code' => 'breakdown',
                'reason' => 'Engine overheating on expressway, replaced with CAP-1302',
                'billing_continuity_rule' => 'continue_period',
                'old_return' => [
                    'odometer' => '137597.000000',
                    'fuel_level_percent' => '50.00',
                    'condition_summary' => 'Engine hot',
                ],
                'new_handover' => [
                    'odometer' => '190491.000000',
                    'fuel_level_percent' => '100.00',
                    'condition_summary' => 'Clean replacement vehicle',
                ],
            ], null);

            // 3. Verify replacement record
            self::assertInstanceOf(RentalVehicleReplacement::class, $replacement);
            self::assertSame(RentalReplacementStatus::Completed, $replacement->status);
            self::assertSame('breakdown', $replacement->reason_code);
            self::assertSame('Engine overheating on expressway, replaced with CAP-1302', $replacement->reason);

            // 4. Verify old allocation status is Replaced and has end odometer
            $oldAllocation->refresh();
            self::assertSame(RentalAllocationStatus::Replaced, $oldAllocation->status);
            self::assertSame('137597.000000', (string) $oldAllocation->end_odometer);

            // 5. Verify new allocation is active, linked, and has new starting odometer
            $newAllocation = RentalVehicleAllocation::query()->findOrFail($replacement->new_allocation_id);
            self::assertSame(RentalAllocationStatus::Active, $newAllocation->status);
            self::assertSame($oldAllocation->getKey(), $newAllocation->replaces_allocation_id);
            self::assertSame($newVehicleId, $newAllocation->vehicle_id);
            self::assertSame('190491.000000', (string) $newAllocation->start_odometer);

            // 6. Verify custody events
            $returnEvent = $oldAllocation->custodyEvents()->where('event_type', RentalCustodyEventType::ReplacementOut->value)->first();
            self::assertNotNull($returnEvent);
            self::assertSame('137597.000000', (string) $returnEvent->odometer);

            $handoverEvent = $newAllocation->custodyEvents()->where('event_type', RentalCustodyEventType::ReplacementIn->value)->first();
            self::assertNotNull($handoverEvent);
            self::assertSame('190491.000000', (string) $handoverEvent->odometer);

            // 7. Verify relations on RentalAllocationService
            $relations = $allocationService->relations();
            self::assertContains('replacementAllocation.vehicle', $relations);
            self::assertContains('replacesAllocation.vehicle', $relations);
            self::assertContains('replacementRecord', $relations);
            self::assertContains('replacementAsNew', $relations);

            $loadedOld = $oldAllocation->load($relations);
            self::assertNotNull($loadedOld->replacementAllocation);
            self::assertNotNull($loadedOld->replacementRecord);
            self::assertSame('breakdown', $loadedOld->replacementRecord->reason_code);

            $loadedNew = $newAllocation->load($relations);
            self::assertNotNull($loadedNew->replacesAllocation);
            self::assertNotNull($loadedNew->replacementAsNew);
            self::assertSame('breakdown', $loadedNew->replacementAsNew->reason_code);
        });
    }

    public function test_vehicle_replacement_with_different_owner_supply_vehicle(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $supplier1Id = $this->createSupplier($tenantId, 'Perera Fleet Holdings', 'SUP-PERERA');
        $supplier2Id = $this->createSupplier($tenantId, 'Fernando Logistics Ltd', 'SUP-FERNANDO');
        $vehicle1Id = $this->createVehicle($tenantId, 'CBC-9180', '137000.000000');
        $vehicle2Id = $this->createVehicle($tenantId, 'CAP-1302', '190000.000000');
        $ownership1Id = $this->createOwnership($tenantId, $vehicle1Id, $supplier1Id, 'Perera Fleet Holdings');
        $ownership2Id = $this->createOwnership($tenantId, $vehicle2Id, $supplier2Id, 'Fernando Logistics Ltd');

        $this->withTenantExecutionContext($tenantId, function () use (
            $tenantId, $customerId, $currencyId,
            $supplier1Id, $supplier2Id,
            $vehicle1Id, $vehicle2Id,
            $ownership1Id, $ownership2Id
        ): void {
            $agreementService = app(RentalAgreementService::class);
            $allocationService = app(RentalAllocationService::class);
            $custodyService = app(RentalCustodyService::class);
            $replacementService = app(RentalReplacementService::class);

            // 1. Owner 1 Agreement (Perera Fleet Holdings) for CBC-9180
            $owner1Agreement = $agreementService->create([
                'agreement_number' => 'RA-OWN-PERERA-001',
                'agreement_kind' => 'owner_supply',
                'supplier_id' => $supplier1Id,
                'agreement_date' => '2026-07-01',
                'starts_at' => '2026-07-01 00:00:00',
                'ends_at' => '2026-07-31 23:59:59',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-01 00:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '3000.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '100000.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null);
            $owner1Agreement->update(['status' => 'active']);

            // 2. Owner 1 Allocation & Receipt by company
            $owner1Allocation = $allocationService->create($owner1Agreement, [
                'vehicle_id' => $vehicle1Id,
                'vehicle_source_type' => 'owner_supplied',
                'vehicle_ownership_id' => $ownership1Id,
                'allocated_from' => '2026-07-01 00:00:00',
                'allocated_to' => '2026-07-31 23:59:59',
                'start_odometer' => '137130.000000',
            ], null);
            $receipt1 = $custodyService->create($owner1Allocation, [
                'event_type' => RentalCustodyEventType::OwnerToCompany->value,
                'occurred_at' => '2026-07-01 08:00:00',
                'odometer' => '137130.000000',
            ], null);
            $custodyService->confirm($receipt1, (int) $receipt1->row_version, null);
            $owner1Allocation->refresh();
            self::assertSame(RentalAllocationStatus::Active, $owner1Allocation->status);

            // 3. Owner 2 Agreement (Fernando Logistics Ltd - DIFFERENT OWNER) for CAP-1302
            $owner2Agreement = $agreementService->create([
                'agreement_number' => 'RA-OWN-FERN-002',
                'agreement_kind' => 'owner_supply',
                'supplier_id' => $supplier2Id,
                'agreement_date' => '2026-07-01',
                'starts_at' => '2026-07-01 00:00:00',
                'ends_at' => '2026-07-31 23:59:59',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-01 00:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '3000.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '120000.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null);
            $owner2Agreement->update(['status' => 'active']);

            // 4. Owner 2 Allocation & Receipt by company
            $owner2Allocation = $allocationService->create($owner2Agreement, [
                'vehicle_id' => $vehicle2Id,
                'vehicle_source_type' => 'owner_supplied',
                'vehicle_ownership_id' => $ownership2Id,
                'allocated_from' => '2026-07-01 00:00:00',
                'allocated_to' => '2026-07-31 23:59:59',
                'start_odometer' => '190491.000000',
            ], null);
            $receipt2 = $custodyService->create($owner2Allocation, [
                'event_type' => RentalCustodyEventType::OwnerToCompany->value,
                'occurred_at' => '2026-07-06 10:00:00',
                'odometer' => '190491.000000',
            ], null);
            $custodyService->confirm($receipt2, (int) $receipt2->row_version, null);
            $owner2Allocation->refresh();
            self::assertSame(RentalAllocationStatus::Active, $owner2Allocation->status);

            // 5. Customer Rental Agreement (Unilever)
            $customerAgreement = $agreementService->create([
                'agreement_number' => 'RA-CUST-UNILEVER-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-01',
                'starts_at' => '2026-07-01 00:00:00',
                'ends_at' => '2026-07-31 23:59:59',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-01 00:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '3000.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '160000.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null);
            $customerAgreement->update(['status' => 'active']);

            // 6. Customer Allocation for Vehicle 1 (sourced from Owner 1)
            $customerAllocation1 = $allocationService->create($customerAgreement, [
                'vehicle_id' => $vehicle1Id,
                'vehicle_source_type' => 'owner_supplied',
                'source_allocation_id' => $owner1Allocation->getKey(),
                'allocated_from' => '2026-07-01 08:00:00',
                'allocated_to' => '2026-07-31 18:00:00',
                'start_odometer' => '137130.000000',
            ], null);

            $handover1 = $custodyService->create($customerAllocation1, [
                'event_type' => RentalCustodyEventType::CompanyToCustomer->value,
                'occurred_at' => '2026-07-01 08:00:00',
                'odometer' => '137130.000000',
                'from_role' => 'company',
                'to_role' => 'customer',
            ], null);
            $custodyService->confirm($handover1, (int) $handover1->row_version, null);
            $customerAllocation1->refresh();
            self::assertSame(RentalAllocationStatus::Active, $customerAllocation1->status);

            // 7. Perform replacement: Replace Vehicle 1 (Owner 1) with Vehicle 2 (Owner 2 - DIFFERENT OWNER!)
            $replacement = $replacementService->replace($customerAllocation1, [
                'new_vehicle_id' => $vehicle2Id,
                'vehicle_source_type' => 'owner_supplied',
                'source_allocation_id' => $owner2Allocation->getKey(),
                'replacement_at' => '2026-07-06 14:00:00',
                'reason_code' => 'breakdown',
                'reason' => 'CBC-9180 engine breakdown. Replaced with CAP-1302 sourced from Fernando Logistics Ltd.',
                'billing_continuity_rule' => 'continue_period',
                'old_return' => [
                    'odometer' => '137597.000000',
                    'fuel_level_percent' => '50.00',
                    'condition_summary' => 'Overheated engine',
                ],
                'new_handover' => [
                    'odometer' => '190491.000000',
                    'fuel_level_percent' => '100.00',
                    'condition_summary' => 'Supplied by Fernando Logistics, in good order',
                ],
            ], null);

            // 8. Assertions
            self::assertInstanceOf(RentalVehicleReplacement::class, $replacement);
            self::assertSame(RentalReplacementStatus::Completed, $replacement->status);

            // Old allocation: replaced, end odometer, belongs to Owner 1
            $customerAllocation1->refresh()->load($allocationService->relations());
            self::assertSame(RentalAllocationStatus::Replaced, $customerAllocation1->status);
            self::assertSame('137597.000000', (string) $customerAllocation1->end_odometer);
            self::assertSame($owner1Allocation->getKey(), $customerAllocation1->source_allocation_id);
            self::assertSame($supplier1Id, $customerAllocation1->sourceAllocation->agreement->supplier_id);
            self::assertSame('Perera Fleet Holdings', $customerAllocation1->sourceAllocation->agreement->supplier->name);

            // New allocation: active, start odometer, sourced from Owner 2 (DIFFERENT OWNER!)
            $customerAllocation2 = RentalVehicleAllocation::query()->findOrFail($replacement->new_allocation_id);
            $customerAllocation2->load($allocationService->relations());
            self::assertSame(RentalAllocationStatus::Active, $customerAllocation2->status);
            self::assertSame('190491.000000', (string) $customerAllocation2->start_odometer);
            self::assertSame($customerAllocation1->getKey(), $customerAllocation2->replaces_allocation_id);
            self::assertSame($owner2Allocation->getKey(), $customerAllocation2->source_allocation_id);
            self::assertSame($supplier2Id, $customerAllocation2->sourceAllocation->agreement->supplier_id);
            self::assertSame('Fernando Logistics Ltd', $customerAllocation2->sourceAllocation->agreement->supplier->name);
            self::assertSame($ownership2Id, $customerAllocation2->vehicle_ownership_id);

            // Verify owners are distinct!
            self::assertNotSame(
                $customerAllocation1->sourceAllocation->agreement->supplier_id,
                $customerAllocation2->sourceAllocation->agreement->supplier_id
            );

            // Verify custody events and odometers
            $returnEvent = $customerAllocation1->custodyEvents()->where('event_type', RentalCustodyEventType::ReplacementOut->value)->first();
            self::assertNotNull($returnEvent);
            self::assertSame('137597.000000', (string) $returnEvent->odometer);

            $handoverEvent = $customerAllocation2->custodyEvents()->where('event_type', RentalCustodyEventType::ReplacementIn->value)->first();
            self::assertNotNull($handoverEvent);
            self::assertSame('190491.000000', (string) $handoverEvent->odometer);

            // Verify bidirectional traceability links
            self::assertSame($customerAllocation2->getKey(), $customerAllocation1->replacementAllocation->getKey());
            self::assertSame($customerAllocation1->getKey(), $customerAllocation2->replacesAllocation->getKey());
            self::assertSame($replacement->getKey(), $customerAllocation1->replacementRecord->getKey());
            self::assertSame($replacement->getKey(), $customerAllocation2->replacementAsNew->getKey());
        });
    }

    private function createSupplier(int $tenantId, string $name, string $code): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'supplier_number' => 'SUP-'.uniqid(),
            'code' => $code,
            'name' => $name,
            'legal_name' => $name,
            'display_name' => $name,
            'supplier_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOwnership(int $tenantId, int $vehicleId, int $supplierId, string $ownerName): int
    {
        return (int) DB::table('vehicle_ownerships')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_id' => $vehicleId,
            'owner_type' => 'supplier',
            'owner_id' => $supplierId,
            'owner_key' => 'supplier:'.$supplierId,
            'owner_code_snapshot' => 'SUP-'.$supplierId,
            'owner_name_snapshot' => $ownerName,
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
            'code' => 'LKR',
            'name' => 'Sri Lankan Rupee',
            'symbol' => 'Rs',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTenant(int $currencyId): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'code' => 'VR-REPL-'.uniqid(),
            'name' => 'Replacement Fleet Tenant',
            'slug' => 'replacement-fleet-tenant-'.uniqid(),
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
            'customer_number' => 'CUS-'.uniqid(),
            'code' => 'CUS-REPL-'.uniqid(),
            'name' => 'Unilever Sri Lanka Ltd',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createVehicle(int $tenantId, string $plateNumber, string $odometer): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'vehicle_number' => $plateNumber,
            'registration_number' => $plateNumber,
            'odometer_reading' => $odometer,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
