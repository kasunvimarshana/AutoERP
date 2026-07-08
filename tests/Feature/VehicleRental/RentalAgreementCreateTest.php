<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalRateVersionService;
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

    public function test_usage_period_crossing_rate_version_boundary_is_rejected(): void
    {
        $currencyId = $this->createCurrency('VRB');
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create([
                'agreement_number' => 'RA-RATE-BOUNDARY-001',
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
                    'effective_to' => '2026-07-15 08:00:00',
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

        $service = app(RentalRateVersionService::class);
        $secondVersion = $this->withTenantExecutionContext(
            $tenantId,
            fn () => $service->createDraft($agreement->refresh(), [
                'effective_from' => '2026-07-15 08:00:00',
                'effective_to' => '2026-08-07 08:00:00',
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
            ]),
        );
        $this->withTenantExecutionContext(
            $tenantId,
            fn () => $service->activate($secondVersion, (int) $secondVersion->row_version),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Usage period must stay inside one active rental rate version.');

        $this->withTenantExecutionContext(
            $tenantId,
            fn () => $service->assertSingleVersionCoversPeriod(
                $agreement->refresh(),
                CarbonImmutable::parse('2026-07-14 08:00:00'),
                CarbonImmutable::parse('2026-07-16 08:00:00'),
            ),
        );
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
        self::assertSame('customer_rental', $agreement->depositRequirement->agreement_kind->value);
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

    public function test_agreement_activation_requires_execution_and_printable_terms(): void
    {
        $currencyId = $this->createCurrency('VRA');
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $payload = $this->completeAgreementPayload(
            'LE-ACTIVATION-INCOMPLETE',
            'customer_rental',
            $customerId,
            $currencyId,
        );
        unset($payload['executed_at'], $payload['terms']);

        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create(
                $payload,
                $tenantId,
                null,
                null,
            ),
        );

        $this->expectException(ValidationException::class);

        $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->transition(
                $agreement,
                RentalAgreementStatus::Active,
                (int) $agreement->row_version,
            ),
        );
    }

    public function test_activation_captures_an_immutable_side_specific_document_snapshot(): void
    {
        $currencyId = $this->createCurrency('VRI');
        $tenantId = $this->createTenant($currencyId);
        $supplierId = $this->createSupplier($tenantId, $currencyId);
        $agreement = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create(
                $this->completeAgreementPayload(
                    'LO-SNAPSHOT-001',
                    'owner_supply',
                    $supplierId,
                    $currencyId,
                ),
                $tenantId,
                null,
                null,
            ),
        );

        $activated = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->transition(
                $agreement,
                RentalAgreementStatus::Active,
                (int) $agreement->row_version,
            ),
        );
        $snapshot = $activated->metadata['document_snapshot'] ?? null;

        self::assertSame(RentalAgreementStatus::Active, $activated->status);
        self::assertIsArray($snapshot);
        self::assertSame('owner_supply', $snapshot['agreement_kind']);
        self::assertSame('supplier', $snapshot['party']['type']);
        self::assertSame('Vehicle Rental Lessor', $snapshot['party']['name']);
        self::assertSame('Owner supply terms', $snapshot['terms'][0]['title']);
        self::assertSame('750.000000', $snapshot['rate_version']['components'][0]['rate']);

        DB::table('suppliers')->where('id', $supplierId)->update([
            'name' => 'Renamed Vehicle Owner',
            'updated_at' => now(),
        ]);

        $persistedSnapshot = $activated->refresh()->metadata['document_snapshot'];
        self::assertSame('Vehicle Rental Lessor', $persistedSnapshot['party']['name']);
    }

    public function test_agreement_kind_filter_keeps_lessor_and_lessee_records_separate(): void
    {
        $currencyId = $this->createCurrency('VRF');
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $supplierId = $this->createSupplier($tenantId, $currencyId);
        $service = app(RentalAgreementService::class);

        $this->withTenantExecutionContext($tenantId, function () use (
            $service,
            $tenantId,
            $customerId,
            $supplierId,
            $currencyId,
        ): void {
            $service->create(
                $this->completeAgreementPayload(
                    'LE-FILTER-001',
                    'customer_rental',
                    $customerId,
                    $currencyId,
                ),
                $tenantId,
                null,
                null,
            );
            $service->create(
                $this->completeAgreementPayload(
                    'LO-FILTER-001',
                    'owner_supply',
                    $supplierId,
                    $currencyId,
                ),
                $tenantId,
                null,
                null,
            );
        });

        $lessee = $this->withTenantExecutionContext(
            $tenantId,
            fn () => $service->paginate(
                $tenantId,
                null,
                ['agreement_kind' => 'customer_rental'],
                25,
            ),
        );
        $lessor = $this->withTenantExecutionContext(
            $tenantId,
            fn () => $service->paginate(
                $tenantId,
                null,
                ['agreement_kind' => 'owner_supply'],
                25,
            ),
        );

        self::assertSame(['LE-FILTER-001'], $lessee->getCollection()->pluck('agreement_number')->all());
        self::assertSame(['LO-FILTER-001'], $lessor->getCollection()->pluck('agreement_number')->all());
    }

    /** @return array<string, mixed> */
    private function completeAgreementPayload(
        string $number,
        string $kind,
        int $partyId,
        int $currencyId,
    ): array {
        $agreementDate = now()->subDay()->toDateString();
        $startsAt = now()->subDay()->startOfHour()->toDateTimeString();
        $endsAt = now()->addMonth()->startOfHour()->toDateTimeString();
        $isCustomerRental = $kind === 'customer_rental';

        return [
            'agreement_number' => $number,
            'agreement_kind' => $kind,
            'customer_id' => $isCustomerRental ? $partyId : null,
            'supplier_id' => $isCustomerRental ? null : $partyId,
            'agreement_date' => $agreementDate,
            'executed_at' => now()->subMinute()->toDateTimeString(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'legal_context' => 'company',
            'rental_mode' => 'with_driver',
            'billing_cycle' => 'monthly',
            'billing_basis' => 'calendar_month',
            'proration_rule' => 'exact_day_count',
            'payment_term_days' => 30,
            'currency_id' => $currencyId,
            'terms' => [[
                'sequence' => 1,
                'title' => $isCustomerRental ? 'Customer rental terms' : 'Owner supply terms',
                'content' => $isCustomerRental
                    ? 'The customer accepts the recorded billable rates.'
                    : 'The owner accepts the recorded payable rates.',
                'is_printable' => true,
            ]],
            'rate_version' => [
                'effective_from' => $startsAt,
                'excess_km_method' => 'period',
                'included_km' => '0.000000',
                'currency_id' => $currencyId,
                'components' => [[
                    'component_code' => 'base_rental',
                    'unit' => 'month',
                    'rate' => $isCustomerRental ? '1250.000000' : '750.000000',
                    'multiplier' => '1.000000',
                    'calculation_order' => 1,
                    'is_taxable' => true,
                ]],
            ],
            'activate_rate_version' => true,
        ];
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
