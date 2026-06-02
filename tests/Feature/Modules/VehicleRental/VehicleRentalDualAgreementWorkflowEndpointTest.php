<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VehicleRentalDualAgreementWorkflowEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_linked_lessee_lessor_agreements_and_dual_running_chart_are_persisted_separately(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();
        [$customerId, $supplierId, $rentalVehicleId] = $this->ensureRentalMasterData($tenantId, $organizationUnitId);

        $agreementResponse = $this->withHeaders($headers)->postJson('/api/vehicle-rental/agreements/linked', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $customerId,
            'provider_id' => $supplierId,
            'lessor_party_type' => 'supplier',
            'lessor_party_id' => $supplierId,
            'rental_vehicle_id' => $rentalVehicleId,
            'agreement_date' => '2026-06-02',
            'start_datetime' => '2026-06-02 08:00:00',
            'end_datetime' => '2026-06-05 18:00:00',
            'rate_model' => 'day',
            'driver_mode' => 'without_driver',
            'lessee_rates' => [[
                'rate_name' => 'Customer daily rate',
                'charge_scope' => 'customer',
                'rate_model' => 'day',
                'usage_basis' => 'day',
                'effective_from' => '2026-06-02 08:00:00',
                'base_rate' => 12000,
                'extra_km_rate' => 120,
                'is_default' => true,
            ]],
            'lessor_rates' => [[
                'rate_name' => 'Provider daily rate',
                'charge_scope' => 'provider',
                'rate_model' => 'day',
                'usage_basis' => 'day',
                'effective_from' => '2026-06-02 08:00:00',
                'base_rate' => 8000,
                'extra_km_rate' => 75,
                'is_default' => true,
            ]],
        ]);

        $agreementResponse->assertOk();
        $lesseeAgreementId = (int) $agreementResponse->json('data.lessee_agreement.id');
        $lessorAgreementId = (int) $agreementResponse->json('data.lessor_agreement.id');

        self::assertGreaterThan(0, $lesseeAgreementId);
        self::assertGreaterThan(0, $lessorAgreementId);
        self::assertNotSame($lesseeAgreementId, $lessorAgreementId);

        $this->assertDatabaseHas('vehicle_rental_agreements', [
            'id' => $lesseeAgreementId,
            'agreement_role' => 'lessee',
            'lessor_agreement_id' => $lessorAgreementId,
            'customer_id' => $customerId,
        ]);
        $this->assertDatabaseHas('vehicle_rental_agreements', [
            'id' => $lessorAgreementId,
            'agreement_role' => 'lessor',
            'lessee_agreement_id' => $lesseeAgreementId,
            'provider_id' => $supplierId,
        ]);

        $chartResponse = $this->withHeaders($headers)->postJson('/api/vehicle-rental/running-charts/combined-entry', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'lessee_agreement_id' => $lesseeAgreementId,
            'lessor_agreement_id' => $lessorAgreementId,
            'rental_vehicle_id' => $rentalVehicleId,
            'date' => '2026-06-03',
            'start_meter' => 1000,
            'end_meter' => 1140,
            'running_distance' => 140,
            'duration_hours' => 8,
            'fuel' => 2500,
            'driver_charges' => 0,
            'mileage_charges' => 0,
            'deductions' => 500,
            'notes' => 'Dual-side running entry.',
        ]);

        $chartResponse->assertOk();
        $lesseeChartId = (int) $chartResponse->json('data.lessee_running_chart.id');
        $lessorChartId = (int) $chartResponse->json('data.lessor_running_chart.id');

        $this->assertDatabaseHas('vehicle_rental_running_charts', [
            'id' => $lesseeChartId,
            'agreement_id' => $lesseeAgreementId,
            'agreement_side' => 'lessee',
            'lessor_agreement_id' => $lessorAgreementId,
        ]);
        $this->assertDatabaseHas('vehicle_rental_running_charts', [
            'id' => $lessorChartId,
            'agreement_id' => $lessorAgreementId,
            'agreement_side' => 'lessor',
            'lessee_agreement_id' => $lesseeAgreementId,
        ]);

        $lesseeLine = DB::table('vehicle_rental_running_chart_lines')->where('running_chart_id', $lesseeChartId)->first();
        $lessorLine = DB::table('vehicle_rental_running_chart_lines')->where('running_chart_id', $lessorChartId)->first();
        self::assertNotNull($lesseeLine);
        self::assertNotNull($lessorLine);
        self::assertGreaterThan(0, (float) $lesseeLine->customer_charge_amount);
        self::assertSame(0.0, (float) $lesseeLine->provider_cost_amount);
        self::assertSame(0.0, (float) $lessorLine->customer_charge_amount);
        self::assertGreaterThan(0, (float) $lessorLine->provider_cost_amount);
    }

    /**
     * @return array{0:int,1:int,2:array<string,string>}
     */
    private function authenticatedHeaders(): array
    {
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');

        $loginResponse = $this->postJson('/api/auth/login', [
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'provider_key' => 'internal',
            'tenant_id' => $tenantId,
        ]);
        $loginResponse->assertOk();

        return [
            $tenantId,
            $organizationUnitId,
            [
                'Authorization' => 'Bearer ' . (string) $loginResponse->json('data.tokens.access_token'),
                'X-Organization-Unit-ID' => (string) $organizationUnitId,
                'X-Tenant-ID' => (string) $tenantId,
            ],
        ];
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function ensureRentalMasterData(int $tenantId, int $organizationUnitId): array
    {
        $customerId = (int) DB::table('customers')->where('tenant_id', $tenantId)->value('id');
        $supplierId = (int) DB::table('suppliers')->where('tenant_id', $tenantId)->value('id');
        $vehicleId = (int) DB::table('vehicles')->where('tenant_id', $tenantId)->value('id');

        $rentalVehicleId = DB::table('vehicle_rental_vehicles')->insertGetId([
            'row_version' => 1,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_id' => $vehicleId > 0 ? $vehicleId : null,
            'provider_id' => $supplierId > 0 ? $supplierId : null,
            'source_type' => 'external_provider',
            'availability_status' => 'available',
            'rental_status' => 'available',
            'maintenance_status' => 'clear',
            'internal_code' => 'VR-TEST-001',
            'display_name' => 'Rental Test Vehicle',
            'registration_number' => 'RENT-001',
            'make_model' => 'Toyota HiAce',
            'supports_with_driver' => true,
            'supports_without_driver' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$customerId, $supplierId, (int) $rentalVehicleId];
    }
}
