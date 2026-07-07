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
use Modules\VehicleRental\Http\Resources\RentalCalculationRunResource;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalCalculationRun;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalCalculationService;
use Modules\VehicleRental\Services\RentalCalculationTransitionService;
use Modules\VehicleRental\Services\RentalExpenseService;
use Tests\TestCase;

final class RentalCalculationEndToEndFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_base_and_driver_salary_prorate_by_calendar_month_days(): void
    {
        $fixture = $this->calculationFixture();

        $run = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): RentalCalculationRun => app(RentalCalculationService::class)->calculate(
                $fixture['agreement']->refresh(),
                RentalFinancialSide::Revenue,
                '2026-07-01 00:00:00',
                '2026-07-15 23:59:59',
                (int) $fixture['agreement']->row_version,
                null,
            ),
        );

        $lines = $run->lines->keyBy(fn ($line): string => $line->component_code->value);

        self::assertSame('1500.000000', (string) $lines['base_rental']->net_amount);
        self::assertSame('300.000000', (string) $lines['driver_salary']->net_amount);
        self::assertSame('0.483870', (string) $lines['driver_salary']->chargeable_quantity);
        self::assertSame('driver_salary_month', $lines['driver_salary']->rule_snapshot['quantity_strategy']);
        self::assertSame('calendar_month', $lines['driver_salary']->rule_snapshot['billing_basis']);
        self::assertSame('exact_day_count', $lines['driver_salary']->rule_snapshot['proration_rule']);
    }

    public function test_no_proration_monthly_driver_salary_charges_one_started_cycle(): void
    {
        $fixture = $this->calculationFixture(prorationRule: 'no_proration');

        $run = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): RentalCalculationRun => app(RentalCalculationService::class)->calculate(
                $fixture['agreement']->refresh(),
                RentalFinancialSide::Revenue,
                '2026-07-01 00:00:00',
                '2026-07-15 23:59:59',
                (int) $fixture['agreement']->row_version,
                null,
            ),
        );

        $driverLine = $run->lines->first(
            fn ($line): bool => $line->component_code->value === 'driver_salary',
        );

        self::assertNotNull($driverLine);
        self::assertSame('1.000000', (string) $driverLine->chargeable_quantity);
        self::assertSame('620.000000', (string) $driverLine->net_amount);
    }

    public function test_hourly_driver_salary_uses_working_minutes_as_hours(): void
    {
        $fixture = $this->calculationFixture(driverUnit: 'hour', driverRate: '10.000000', baseRate: '0.000000');

        $run = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): RentalCalculationRun => app(RentalCalculationService::class)->calculate(
                $fixture['agreement']->refresh(),
                RentalFinancialSide::Revenue,
                '2026-07-01 00:00:00',
                '2026-07-15 23:59:59',
                (int) $fixture['agreement']->row_version,
                null,
            ),
        );

        $driverLine = $run->lines->first(
            fn ($line): bool => $line->component_code->value === 'driver_salary',
        );

        self::assertNotNull($driverLine);
        self::assertSame('8.000000', (string) $driverLine->chargeable_quantity);
        self::assertSame('80.000000', (string) $driverLine->net_amount);
    }

    public function test_unsupported_driver_salary_unit_is_rejected_when_rate_is_created(): void
    {
        $currencyId = $this->createCurrency('VRX');
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver Salary does not support km rates.');

        $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create(
                $this->agreementPayload($customerId, $currencyId, 'exact_day_count', 'km'),
                $tenantId,
                null,
                null,
            ),
        );
    }

    public function test_calculation_approval_and_reversal_version_child_rows_and_expense_lifecycle(): void
    {
        $fixture = $this->calculationFixture(withExpense: true);

        $run = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): RentalCalculationRun => app(RentalCalculationService::class)->calculate(
                $fixture['agreement']->refresh(),
                RentalFinancialSide::Revenue,
                '2026-07-01 00:00:00',
                '2026-07-15 23:59:59',
                (int) $fixture['agreement']->row_version,
                null,
            ),
        );

        $submitted = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): RentalCalculationRun => app(RentalCalculationTransitionService::class)->transition(
                $run,
                RentalCalculationStatus::Submitted,
                (int) $run->row_version,
                null,
            ),
        );
        $approved = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): RentalCalculationRun => app(RentalCalculationTransitionService::class)->transition(
                $submitted,
                RentalCalculationStatus::Approved,
                (int) $submitted->row_version,
                null,
            ),
        );

        $this->assertDatabaseHas('rental_expense_allocations', [
            'id' => $fixture['expense_allocation_id'],
            'status' => 'consumed',
            'row_version' => 3,
        ]);
        $this->assertDatabaseHas('rental_expenses', [
            'id' => $fixture['expense_id'],
            'status' => 'allocated',
            'row_version' => 4,
        ]);
        $this->assertDatabaseHas('rental_status_histories', [
            'entity_type' => 'RentalExpense',
            'entity_id' => $fixture['expense_id'],
            'old_status' => 'approved',
            'new_status' => 'allocated',
        ]);

        $lineVersionsAfterApproval = DB::table('rental_calculation_lines')
            ->where('calculation_run_id', $approved->getKey())
            ->pluck('row_version')
            ->unique()
            ->values()
            ->all();
        self::assertSame([2], $lineVersionsAfterApproval);
        $this->assertDatabaseHas('rental_calculation_sources', [
            'calculation_run_id' => $approved->getKey(),
            'row_version' => 2,
        ]);
        $this->assertDatabaseHas('rental_billing_periods', [
            'id' => $approved->billing_period_id,
            'status' => 'finalized',
            'row_version' => 2,
        ]);

        $resource = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): array => (new RentalCalculationRunResource(
                $approved->refresh()->load(app(RentalCalculationService::class)->relations()),
            ))->resolve(),
        );
        self::assertSame(2, $resource['billing_period']['row_version']);
        self::assertSame(2, $resource['lines'][0]['row_version']);

        $reversed = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): RentalCalculationRun => app(RentalCalculationTransitionService::class)->transition(
                $approved,
                RentalCalculationStatus::Reversed,
                (int) $approved->row_version,
                null,
            ),
        );

        $this->assertDatabaseHas('rental_expense_allocations', [
            'id' => $fixture['expense_allocation_id'],
            'status' => 'approved',
            'row_version' => 4,
        ]);
        $this->assertDatabaseHas('rental_expenses', [
            'id' => $fixture['expense_id'],
            'status' => 'approved',
            'row_version' => 5,
        ]);
        $this->assertDatabaseHas('rental_status_histories', [
            'entity_type' => 'RentalExpense',
            'entity_id' => $fixture['expense_id'],
            'old_status' => 'allocated',
            'new_status' => 'approved',
        ]);
        $this->assertDatabaseHas('rental_billing_periods', [
            'id' => $reversed->billing_period_id,
            'status' => 'reopened',
            'row_version' => 3,
        ]);
    }

    /**
     * @return array{
     *     tenant_id:int,
     *     agreement:RentalAgreement,
     *     expense_id?:int,
     *     expense_allocation_id?:int
     * }
     */
    private function calculationFixture(
        string $prorationRule = 'exact_day_count',
        string $driverUnit = 'month',
        string $driverRate = '620.000000',
        string $baseRate = '3100.000000',
        bool $withExpense = false,
    ): array {
        $currencyId = $this->createCurrency('VRC');
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $vehicleId = $this->createVehicle($tenantId);

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create(
                $this->agreementPayload($customerId, $currencyId, $prorationRule, $driverUnit, $driverRate, $baseRate),
                $tenantId,
                null,
                null,
            ),
        );

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

        $fixture = [
            'tenant_id' => $tenantId,
            'agreement' => $agreement,
        ];

        if ($withExpense) {
            $expense = $this->withTenantExecutionContext(
                $tenantId,
                fn () => app(RentalExpenseService::class)->create([
                    'vehicle_id' => $vehicleId,
                    'expense_type' => 'repair',
                    'expense_date' => '2026-07-10',
                    'currency_id' => $currencyId,
                    'net_amount' => '100.000000',
                    'tax_amount' => '0.000000',
                    'reference_number' => 'EXP-CALC-001',
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
            $expenseVersion = (int) DB::table('rental_expenses')
                ->where('id', $expense->getKey())
                ->value('row_version');
            $submitted = $this->withTenantExecutionContext(
                $tenantId,
                fn () => app(RentalExpenseService::class)->transition($expense, RentalExpenseStatus::Submitted, $expenseVersion),
            );
            $submittedVersion = (int) DB::table('rental_expenses')
                ->where('id', $submitted->getKey())
                ->value('row_version');
            $approved = $this->withTenantExecutionContext(
                $tenantId,
                fn () => app(RentalExpenseService::class)->transition($submitted, RentalExpenseStatus::Approved, $submittedVersion),
            );
            $allocation = $approved->allocations->first();
            $fixture['expense_id'] = (int) $approved->getKey();
            $fixture['expense_allocation_id'] = (int) $allocation->getKey();
        }

        return $fixture;
    }

    private function agreementPayload(
        int $customerId,
        int $currencyId,
        string $prorationRule,
        string $driverUnit,
        string $driverRate = '620.000000',
        string $baseRate = '3100.000000',
    ): array {
        return [
            'agreement_number' => 'RA-CALC-001',
            'agreement_kind' => 'customer_rental',
            'customer_id' => $customerId,
            'agreement_date' => '2026-07-01',
            'starts_at' => '2026-07-01 00:00:00',
            'ends_at' => '2026-07-31 23:59:59',
            'legal_context' => 'company',
            'rental_mode' => 'with_driver',
            'billing_cycle' => 'monthly',
            'billing_basis' => 'calendar_month',
            'proration_rule' => $prorationRule,
            'payment_term_days' => 30,
            'currency_id' => $currencyId,
            'rate_version' => [
                'effective_from' => '2026-07-01 00:00:00',
                'effective_to' => '2026-07-31 23:59:59',
                'driver_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => $prorationRule,
                'excess_km_method' => 'period',
                'included_km' => '0.000000',
                'currency_id' => $currencyId,
                'components' => [
                    [
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => $baseRate,
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                        'is_taxable' => true,
                    ],
                    [
                        'component_code' => 'driver_salary',
                        'unit' => $driverUnit,
                        'rate' => $driverRate,
                        'multiplier' => '1.000000',
                        'calculation_order' => 2,
                        'is_taxable' => true,
                    ],
                ],
            ],
            'activate_rate_version' => true,
        ];
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
            'usage_number' => 'RUL-CALC-001',
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
            'fingerprint' => hash('sha256', 'usage-'.$tenantId),
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
            'context_fingerprint' => hash('sha256', 'context-'.$tenantId),
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

    private function createCurrency(string $code): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => $code,
            'name' => "Vehicle Rental Calculation Currency {$code}",
            'symbol' => $code,
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
            'code' => 'VR-CALC',
            'name' => 'Vehicle Rental Calculation Tenant',
            'slug' => 'vehicle-rental-calculation-tenant',
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
            'customer_number' => 'CUS-CALC-001',
            'code' => 'CUS-CALC',
            'name' => 'Calculation Customer',
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
            'vehicle_number' => 'VEH-CALC-001',
            'code' => 'VEH-CALC',
            'registration_number' => 'CALC-001',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAllocation(int $tenantId, int $agreementId, int $vehicleId): int
    {
        return (int) DB::table('rental_vehicle_allocations')->insertGetId([
            'tenant_id' => $tenantId,
            'allocation_number' => 'RVA-CALC-001',
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
