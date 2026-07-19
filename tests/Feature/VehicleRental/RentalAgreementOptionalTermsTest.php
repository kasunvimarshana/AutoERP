<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Http\Resources\RentalAgreementResource;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Services\RentalAgreementService;
use Tests\TestCase;

final class RentalAgreementOptionalTermsTest extends TestCase
{
    use RefreshDatabase;

    public function test_agreement_can_be_activated_without_printable_terms(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $startsAt = now()->subHour()->startOfMinute()->toDateTimeString();
        $endsAt = now()->addMonth()->startOfMinute()->toDateTimeString();

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'LE-OPTIONAL-TERMS-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => now()->subDay()->toDateString(),
                'executed_at' => now()->subMinute()->toDateTimeString(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'legal_context' => 'company',
                'rental_mode' => 'self_drive',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'terms' => [],
                'rate_version' => [
                    'effective_from' => $startsAt,
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

        $activated = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->transition(
                $agreement,
                RentalAgreementStatus::Active,
                (int) $agreement->row_version,
            ),
        );

        self::assertSame(RentalAgreementStatus::Active, $activated->status);
        self::assertSame([], $activated->metadata['document_snapshot']['terms'] ?? null);
        self::assertCount(0, $activated->terms);

        $resource = (new RentalAgreementResource($activated))->resolve(request());
        self::assertTrue(
            data_get($resource, 'document_snapshot.rate_version.components.0.is_taxable'),
        );
    }

    private function createCurrency(): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => 'VRO',
            'name' => 'Vehicle Rental Optional Terms Currency',
            'symbol' => 'VRO',
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
            'code' => 'VR-OPTIONAL-TERMS',
            'name' => 'Vehicle Rental Optional Terms Tenant',
            'slug' => 'vehicle-rental-optional-terms-tenant',
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
            'customer_number' => 'CUS-VR-OPTIONAL-TERMS',
            'code' => 'CUS-VR-OPTIONAL',
            'name' => 'Vehicle Rental Optional Terms Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
