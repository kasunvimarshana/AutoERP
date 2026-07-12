<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Services\RentalAgreementService;
use Tests\TestCase;

final class RentalAgreementActivationRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private const ACTIVATION_REQUIREMENT_MESSAGE = 'Execution date and legal context are required before agreement activation.';

    private const CURRENCY_CODE = 'VRA';

    private const TENANT_CODE = 'VR-ACTIVATION';

    private const CUSTOMER_NUMBER = 'CUS-VR-ACTIVATION';

    private const CUSTOMER_CODE = 'CUS-VR-ACT';

    public function test_activation_rejects_missing_execution_date_without_mutating_the_draft(): void
    {
        $agreement = $this->createDraftAgreement('NO-EXECUTION', [
            'executed_at' => null,
        ]);

        $this->assertActivationRequirementFailure($agreement);
    }

    public function test_activation_rejects_missing_legal_context_without_mutating_the_draft(): void
    {
        $agreement = $this->createDraftAgreement('NO-LEGAL-CONTEXT', [
            'legal_context' => null,
        ]);

        $this->assertActivationRequirementFailure($agreement);
    }

    private function assertActivationRequirementFailure(RentalAgreement $agreement): void
    {
        $expectedVersion = (int) $agreement->row_version;

        try {
            $this->withTenantExecutionContext(
                (int) $agreement->tenant_id,
                fn (): RentalAgreement => app(RentalAgreementService::class)->transition(
                    $agreement,
                    RentalAgreementStatus::Active,
                    $expectedVersion,
                ),
            );
            self::fail('Agreement activation must reject incomplete execution context.');
        } catch (ValidationException $exception) {
            self::assertSame(
                [self::ACTIVATION_REQUIREMENT_MESSAGE],
                $exception->errors()['agreement'] ?? [],
            );
        }

        $agreement->refresh();

        self::assertSame(RentalAgreementStatus::Draft, $agreement->status);
        self::assertSame($expectedVersion, (int) $agreement->row_version);
        self::assertArrayNotHasKey('document_snapshot', $agreement->metadata ?? []);
    }

    /** @param array<string, mixed> $overrides */
    private function createDraftAgreement(string $numberSuffix, array $overrides): RentalAgreement
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $startsAt = now()->subHour()->startOfMinute()->toDateTimeString();
        $endsAt = now()->addMonth()->startOfMinute()->toDateTimeString();
        $payload = array_replace([
            'agreement_number' => 'LE-ACTIVATION-'.$numberSuffix,
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
        ], $overrides);

        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalAgreement => app(RentalAgreementService::class)->create(
                $payload,
                $tenantId,
                null,
                null,
            ),
        );
    }

    private function createCurrency(): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => self::CURRENCY_CODE,
            'name' => 'Vehicle Rental Activation Currency',
            'symbol' => self::CURRENCY_CODE,
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
            'code' => self::TENANT_CODE,
            'name' => 'Vehicle Rental Activation Tenant',
            'slug' => 'vehicle-rental-activation-tenant',
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
            'customer_number' => self::CUSTOMER_NUMBER,
            'code' => self::CUSTOMER_CODE,
            'name' => 'Vehicle Rental Activation Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
