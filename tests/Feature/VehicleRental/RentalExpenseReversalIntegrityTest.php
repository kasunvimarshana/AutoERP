<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalCalculationRun;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalCalculationService;
use Modules\VehicleRental\Services\RentalCalculationTransitionService;
use Modules\VehicleRental\Services\RentalExpenseService;
use Tests\TestCase;

final class RentalExpenseReversalIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumed_expense_requires_downstream_calculation_reversal_first(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $vehicleId = $this->createVehicle($tenantId);
        $agreement = $this->createAgreement($tenantId, $customerId, $currencyId);
        $allocationId = $this->createAllocation($tenantId, (int) $agreement->getKey(), $vehicleId);
        $this->createApprovedUsageContext(
            $tenantId,
            $customerId,
            $currencyId,
            $vehicleId,
            $allocationId,
            (int) $agreement->getKey(),
            (int) $agreement->activeRateVersion->getKey(),
        );

        $expense = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalExpense => app(RentalExpenseService::class)->create([
                'vehicle_id' => $vehicleId,
                'expense_type' => 'repair',
                'expense_date' => '2026-07-10',
                'currency_id' => $currencyId,
                'net_amount' => '100.000000',
                'tax_amount' => '0.000000',
                'reference_number' => 'EXP-REVERSAL-001',
                'allocations' => [[
                    'allocation_type' => 'customer_recovery',
                    'target_agreement_id' => $agreement->getKey(),
                    'target_vehicle_allocation_id' => $allocationId,
                    'customer_id' => $customerId,
                    'net_amount' => '100.000000',
                    'tax_amount' => '0.000000',
                    'withholding_amount' => '0.000000',
                    'markup_amount' => '0.000000',
                ]],
            ], $tenantId, null, null),
        );
        $submittedExpense = $this->transitionExpense($tenantId, $expense, RentalExpenseStatus::Submitted);
        $approvedExpense = $this->transitionExpense($tenantId, $submittedExpense, RentalExpenseStatus::Approved);

        $calculated = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalCalculationRun => app(RentalCalculationService::class)->calculate(
                $agreement->refresh(),
                RentalFinancialSide::Revenue,
                '2026-07-01 00:00:00',
                '2026-07-15 23:59:59',
                (int) $agreement->row_version,
                null,
            ),
        );
        $submittedCalculation = $this->transitionCalculation(
            $tenantId,
            $calculated,
            RentalCalculationStatus::Submitted,
        );
        $approvedCalculation = $this->transitionCalculation(
            $tenantId,
            $submittedCalculation,
            RentalCalculationStatus::Approved,
        );

        $consumedExpense = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalExpense => RentalExpense::query()->findOrFail($approvedExpense->getKey()),
        );
        try {
            $this->withTenantExecutionContext(
                $tenantId,
                fn (): RentalExpense => app(RentalExpenseService::class)->transition(
                    $consumedExpense,
                    RentalExpenseStatus::Reversed,
                    (int) $consumedExpense->row_version,
                    null,
                    'Incorrect repair allocation.',
                ),
            );
            self::fail('Consumed rental expense reversal should have been rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(
                'Reverse the approved rental calculation and its generated financial document before reversing this expense.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseHas('rental_expenses', [
            'id' => $consumedExpense->getKey(),
            'status' => RentalExpenseStatus::Allocated->value,
        ]);
        $this->assertDatabaseHas('rental_expense_allocations', [
            'expense_id' => $consumedExpense->getKey(),
            'status' => 'consumed',
        ]);

        $this->transitionCalculation(
            $tenantId,
            $approvedCalculation,
            RentalCalculationStatus::Reversed,
            'Incorrect repair allocation.',
        );

        $releasedExpense = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalExpense => RentalExpense::query()->findOrFail($approvedExpense->getKey()),
        );
        $reversedExpense = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalExpense => app(RentalExpenseService::class)->transition(
                $releasedExpense,
                RentalExpenseStatus::Reversed,
                (int) $releasedExpense->row_version,
                null,
                'Incorrect repair allocation.',
            ),
        );

        self::assertSame(RentalExpenseStatus::Reversed, $reversedExpense->status);
        $this->assertDatabaseHas('rental_expense_allocations', [
            'expense_id' => $reversedExpense->getKey(),
            'status' => 'reversed',
        ]);
    }

    private function transitionExpense(
        int $tenantId,
        RentalExpense $expense,
        RentalExpenseStatus $status,
    ): RentalExpense {
        return $this->withTenantExecutionContext(
            $tenantId,
            function () use ($expense, $status): RentalExpense {
                $current = RentalExpense::query()->findOrFail($expense->getKey());

                return app(RentalExpenseService::class)->transition(
                    $current,
                    $status,
                    (int) $current->row_version,
                );
            },
        );
    }

    private function transitionCalculation(
        int $tenantId,
        RentalCalculationRun $run,
        RentalCalculationStatus $status,
        ?string $reason = null,
    ): RentalCalculationRun {
        return $this->withTenantExecutionContext(
            $tenantId,
            function () use ($run, $status, $reason): RentalCalculationRun {
                $current = RentalCalculationRun::query()->findOrFail($run->getKey());

                return app(RentalCalculationTransitionService::class)->transition(
                    $current,
                    $status,
                    (int) $current->row_version,
                    null,
                    $reason,
                );
            },
        );
    }

    private function createAgreement(int $tenantId, int $customerId, int $currencyId): RentalAgreement
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'RA-EXPENSE-REVERSAL-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-01',
                'starts_at' => '2026-07-01 00:00:00',
                'ends_at' => '2026-07-31 23:59:59',
                'legal_context' => 'company',
                'rental_mode' => 'vehicle_only',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-01 00:00:00',
                    'effective_to' => '2026-07-31 23:59:59',
                    'driver_mode' => 'vehicle_only',
                    'billing_cycle' => 'monthly',
                    'billing_basis' => 'calendar_month',
                    'proration_rule' => 'exact_day_count',
                    'excess_km_method' => 'period',
                    'included_km' => '0.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '3100.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                        'is_taxable' => true,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null),
        );
    }

    private function createApprovedUsageContext(
        int $tenantId,
        int $customerId,
        int $currencyId,
        int $vehicleId,
        int $allocationId,
        int $agreementId,
        int $rateVersionId,
    ): void {
        $usageId = (int) DB::table('rental_usage_logs')->insertGetId([
            'tenant_id' => $tenantId,
            'usage_number' => 'RUL-EXPENSE-REVERSAL-001',
            'vehicle_allocation_id' => $allocationId,
            'vehicle_id' => $vehicleId,
            'usage_date' => '2026-07-10',
            'started_at' => '2026-07-10 08:00:00',
            'ended_at' => '2026-07-10 16:00:00',
            'start_odometer' => '1000.000000',
            'end_odometer' => '1120.000000',
            'distance_km' => '120.000000',
            'net_operational_distance_km' => '120.000000',
            'working_minutes' => 480,
            'normal_overtime_minutes' => 0,
            'double_overtime_minutes' => 0,
            'triple_overtime_minutes' => 0,
            'night_out_count' => '0.000000',
            'status' => 'approved',
            'fingerprint' => hash('sha256', 'expense-reversal-usage-'.$tenantId),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contextId = (int) DB::table('rental_usage_contexts')->insertGetId([
            'tenant_id' => $tenantId,
            'usage_log_id' => $usageId,
            'financial_side' => 'revenue',
            'agreement_id' => $agreementId,
            'vehicle_allocation_id' => $allocationId,
            'rate_version_id' => $rateVersionId,
            'customer_id' => $customerId,
            'currency_id' => $currencyId,
            'context_fingerprint' => hash('sha256', 'expense-reversal-context-'.$tenantId),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rental_usage_facts')->insert([
            'tenant_id' => $tenantId,
            'usage_context_id' => $contextId,
            'usage_log_id' => $usageId,
            'financial_side' => 'revenue',
            'started_at' => '2026-07-10 08:00:00',
            'ended_at' => '2026-07-10 16:00:00',
            'start_odometer' => '1000.000000',
            'end_odometer' => '1120.000000',
            'commercial_distance_km' => '120.000000',
            'working_minutes' => 480,
            'normal_overtime_minutes' => 0,
            'double_overtime_minutes' => 0,
            'triple_overtime_minutes' => 0,
            'night_out_count' => '0.000000',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCurrency(): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => 'VRR',
            'name' => 'Vehicle Rental Reversal Currency',
            'symbol' => 'VRR',
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
            'code' => 'VR-REVERSAL',
            'name' => 'Vehicle Rental Reversal Tenant',
            'slug' => 'vehicle-rental-reversal-tenant',
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
            'customer_number' => 'CUS-REVERSAL-001',
            'code' => 'CUS-REVERSAL',
            'name' => 'Reversal Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createVehicle(int $tenantId): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_number' => 'VEH-REVERSAL-001',
            'code' => 'VEH-REVERSAL',
            'registration_number' => 'REV-001',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAllocation(int $tenantId, int $agreementId, int $vehicleId): int
    {
        return (int) DB::table('rental_vehicle_allocations')->insertGetId([
            'tenant_id' => $tenantId,
            'allocation_number' => 'RVA-REVERSAL-001',
            'agreement_id' => $agreementId,
            'vehicle_id' => $vehicleId,
            'vehicle_source_type' => 'company_owned',
            'allocated_from' => '2026-07-01 00:00:00',
            'allocated_to' => '2026-07-31 23:59:59',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
