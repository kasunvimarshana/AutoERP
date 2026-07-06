<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Services\RentalAgreementService;
use Tests\TestCase;

final class RentalAgreementCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_inline_rate_version_is_activated_with_persisted_row_version(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'RA-INLINE-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-05',
                'starts_at' => '2026-07-05 08:00:00',
                'ends_at' => '2026-08-05 08:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
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
            ], $tenantId, null, null),
        );

        $activeRateVersion = $agreement->activeRateVersion;

        self::assertNotNull($activeRateVersion);
        self::assertSame(RentalRateVersionStatus::Active, $activeRateVersion->status);
        self::assertSame(2, (int) $activeRateVersion->row_version);
        $this->assertDatabaseHas('rental_agreement_rate_versions', [
            'agreement_id' => $agreement->getKey(),
            'status' => RentalRateVersionStatus::Active->value,
            'row_version' => 2,
        ]);
    }

    public function test_lessor_agreement_is_created_as_supplier_side_owner_supply(): void
    {
        $currencyId = $this->createCurrency('VRL');
        $tenantId = $this->createTenant($currencyId);
        $supplierId = $this->createSupplier($tenantId, $currencyId);

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'LA-INLINE-001',
                'agreement_kind' => 'owner_supply',
                'supplier_id' => $supplierId,
                'agreement_date' => '2026-07-06',
                'starts_at' => '2026-07-06 08:00:00',
                'ends_at' => '2026-08-06 08:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-06 08:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '0.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '750.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                        'is_taxable' => true,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null),
        );

        self::assertSame('owner_supply', $agreement->agreement_kind->value);
        self::assertSame($supplierId, (int) $agreement->supplier_id);
        self::assertNull($agreement->customer_id);
        self::assertNull($agreement->depositRequirement);
        self::assertNotNull($agreement->activeRateVersion);
        self::assertSame(RentalRateVersionStatus::Active, $agreement->activeRateVersion->status);
    }

    public function test_lessee_agreement_is_created_as_customer_side_customer_rental(): void
    {
        $currencyId = $this->createCurrency('VRS');
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'LE-INLINE-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-06',
                'starts_at' => '2026-07-06 08:00:00',
                'ends_at' => '2026-08-06 08:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-06 08:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '0.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '1250.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                        'is_taxable' => true,
                    ]],
                ],
                'activate_rate_version' => true,
                'deposit' => [
                    'required_amount' => '1000.000000',
                    'currency_id' => $currencyId,
                    'is_refundable' => true,
                ],
            ], $tenantId, null, null),
        );

        self::assertSame('customer_rental', $agreement->agreement_kind->value);
        self::assertSame($customerId, (int) $agreement->customer_id);
        self::assertNull($agreement->supplier_id);
        self::assertNotNull($agreement->depositRequirement);
        self::assertSame($customerId, (int) $agreement->depositRequirement->customer_id);
        self::assertSame('1000.000000', (string) $agreement->depositRequirement->required_amount);
        self::assertNotNull($agreement->activeRateVersion);
        self::assertSame(RentalRateVersionStatus::Active, $agreement->activeRateVersion->status);
    }

    public function test_lessor_agreement_rejects_security_deposit_payload(): void
    {
        $currencyId = $this->createCurrency('VRD');
        $tenantId = $this->createTenant($currencyId);
        $supplierId = $this->createSupplier($tenantId, $currencyId);

        $this->expectException(ValidationException::class);

        $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'LA-DEPOSIT-001',
                'agreement_kind' => 'owner_supply',
                'supplier_id' => $supplierId,
                'agreement_date' => '2026-07-07',
                'starts_at' => '2026-07-07 08:00:00',
                'ends_at' => '2026-08-07 08:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'deposit' => [
                    'required_amount' => '1000.000000',
                    'currency_id' => $currencyId,
                ],
            ], $tenantId, null, null),
        );
    }

    public function test_deposit_requirement_schema_rejects_lessor_agreement(): void
    {
        $currencyId = $this->createCurrency('VRK');
        $tenantId = $this->createTenant($currencyId);
        $supplierId = $this->createSupplier($tenantId, $currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'LA-SCHEMA-001',
                'agreement_kind' => 'owner_supply',
                'supplier_id' => $supplierId,
                'agreement_date' => '2026-07-07',
                'starts_at' => '2026-07-07 08:00:00',
                'ends_at' => '2026-08-07 08:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
            ], $tenantId, null, null),
        );

        $this->expectException(QueryException::class);

        DB::table('rental_deposit_requirements')->insert([
            'tenant_id' => $tenantId,
            'agreement_id' => $agreement->getKey(),
            'customer_id' => $customerId,
            'required_amount' => '1000.000000',
            'currency_id' => $currencyId,
            'is_refundable' => true,
            'balance_amount' => '1000.000000',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_structural_draft_edit_is_blocked_after_rate_version_exists(): void
    {
        $currencyId = $this->createCurrency('VRU');
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'LE-LOCK-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-07',
                'starts_at' => '2026-07-07 08:00:00',
                'ends_at' => '2026-08-07 08:00:00',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-07 08:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '0.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '1250.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                        'is_taxable' => true,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null),
        );

        $this->expectException(ValidationException::class);

        $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->updateDraft(
                $agreement,
                ['ends_at' => '2026-08-10 08:00:00'],
                (int) $agreement->row_version,
                null,
            ),
        );
    }

    private function createCurrency(string $code = 'VRT'): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => $code,
            'name' => "Vehicle Rental Test Currency {$code}",
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
            'code' => 'VR-TEST',
            'name' => 'Vehicle Rental Test Tenant',
            'slug' => 'vehicle-rental-test-tenant',
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
            'customer_number' => 'CUS-VR-001',
            'code' => 'CUS-VR',
            'name' => 'Vehicle Rental Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSupplier(int $tenantId, int $currencyId): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_number' => 'SUP-VR-001',
            'code' => 'SUP-VR',
            'name' => 'Vehicle Rental Lessor',
            'supplier_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
