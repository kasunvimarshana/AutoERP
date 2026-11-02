<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    private function createCurrency(): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => 'VRT',
            'name' => 'Vehicle Rental Test Currency',
            'symbol' => 'VRT',
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
}
