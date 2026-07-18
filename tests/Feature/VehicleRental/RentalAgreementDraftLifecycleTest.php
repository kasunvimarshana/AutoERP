<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Services\RentalAgreementService;
use Tests\TestCase;

final class RentalAgreementDraftLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_agreement_and_first_rate_remain_editable_drafts_until_atomic_activation(): void
    {
        $currencyId = $this->createCurrency('RDL');
        $replacementCurrencyId = $this->createCurrency('RDU');
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $replacementCustomerId = $this->createCustomer($tenantId, $replacementCurrencyId, '002');

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'RA-DRAFT-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-01',
                'executed_at' => '2026-07-01',
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
                    'excess_km_method' => 'period',
                    'included_km' => '1000.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '100000.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                        'is_taxable' => true,
                    ]],
                ],
                'deposit' => [
                    'required_amount' => '10000.000000',
                    'currency_id' => $currencyId,
                    'is_refundable' => true,
                ],
            ], $tenantId, null, null),
        );

        self::assertSame(RentalAgreementStatus::Draft, $agreement->status);
        $draftRate = $agreement->rateVersions->sole();
        self::assertSame(RentalRateVersionStatus::Draft, $draftRate->status);
        self::assertNull($agreement->activeRateVersion);

        $updated = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->updateDraft(
                $agreement,
                [
                    'customer_id' => $replacementCustomerId,
                    'starts_at' => '2026-07-02 00:00:00',
                    'ends_at' => '2026-08-01 23:59:59',
                    'currency_id' => $replacementCurrencyId,
                    'rate_version' => [
                        'id' => $draftRate->getKey(),
                        'expected_version' => $draftRate->row_version,
                        'effective_from' => '2026-07-02 00:00:00',
                        'effective_to' => '2026-08-01 23:59:59',
                        'driver_mode' => 'vehicle_only',
                        'billing_cycle' => 'monthly',
                        'billing_basis' => 'calendar_month',
                        'proration_rule' => 'exact_day_count',
                        'excess_km_method' => 'period',
                        'included_km' => '1200.000000',
                        'currency_id' => $replacementCurrencyId,
                        'components' => [[
                            'component_code' => 'base_rental',
                            'unit' => 'month',
                            'rate' => '120000.000000',
                            'multiplier' => '1.000000',
                            'calculation_order' => 1,
                            'is_taxable' => true,
                        ]],
                    ],
                ],
                (int) $agreement->row_version,
                null,
            ),
        );

        $updatedDraftRate = $updated->rateVersions->sole();
        self::assertSame(RentalRateVersionStatus::Draft, $updatedDraftRate->status);
        self::assertSame('1200.000000', (string) $updatedDraftRate->included_km);
        self::assertSame('120000.000000', (string) $updatedDraftRate->components->sole()->rate);
        self::assertSame($replacementCustomerId, (int) $updated->customer_id);
        self::assertSame($replacementCurrencyId, (int) $updated->currency_id);
        self::assertSame($replacementCurrencyId, (int) $updated->depositRequirement->currency_id);

        $activated = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->transition(
                $updated,
                RentalAgreementStatus::Active,
                (int) $updated->row_version,
            ),
        );

        self::assertSame(RentalAgreementStatus::Active, $activated->status);
        self::assertSame(RentalRateVersionStatus::Active, $activated->activeRateVersion->status);
        self::assertSame(
            $updatedDraftRate->getKey(),
            $activated->activeRateVersion->getKey(),
        );
        self::assertSame(
            '120000.000000',
            (string) data_get(
                $activated->metadata,
                'document_snapshot.rate_version.components.0.rate',
            ),
        );
    }

    private function createCurrency(string $code): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => $code,
            'name' => $code.' Currency',
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
            'code' => 'RENTAL-DRAFT-LIFECYCLE',
            'name' => 'Rental Draft Lifecycle Tenant',
            'slug' => 'rental-draft-lifecycle-tenant',
            'base_currency_id' => $currencyId,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCustomer(
        int $tenantId,
        int $currencyId,
        string $suffix = '001',
    ): int {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-DRAFT-'.$suffix,
            'code' => 'CUS-DRAFT-'.$suffix,
            'name' => 'Draft Customer '.$suffix,
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
