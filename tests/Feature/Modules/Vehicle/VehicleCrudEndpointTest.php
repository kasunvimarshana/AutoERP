<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Vehicle;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VehicleCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_vehicle_crud_and_ownership_endpoints_work_with_real_backend_context(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $createResponse = $this->withHeaders($headers)->postJson('/api/vehicle/vehicles', [
            'vehicle_code' => 'VEH-TST-001',
            'license_plate' => 'WP TEST-001',
            'vin' => 'VIN-TST-001',
            'make' => 'Toyota',
            'model' => 'HiAce',
            'year' => 2024,
            'category' => 'Van',
            'current_odometer' => 1200,
            'service_enabled' => true,
            'rental_enabled' => false,
            'usage_profile' => 'service_only',
            'status' => 'active',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.vehicle_code', 'VEH-TST-001')
            ->assertJsonPath('data.license_plate', 'WP TEST-001');

        $vehicleId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicleId,
            'organization_unit_id' => $organizationUnitId,
            'tenant_id' => $tenantId,
            'vehicle_code' => 'VEH-TST-001',
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/vehicle/vehicles?search=TEST-001')
            ->assertOk()
            ->assertJsonPath('data.0.id', $vehicleId);

        $this->withHeaders($headers)
            ->putJson('/api/vehicle/vehicles/' . $vehicleId, [
                'vehicle_code' => 'VEH-TST-001',
                'license_plate' => 'WP TEST-001',
                'vin' => 'VIN-TST-001',
                'make' => 'Toyota',
                'model' => 'HiAce GL',
                'year' => 2024,
                'category' => 'Van',
                'current_odometer' => 1250,
                'service_enabled' => true,
                'rental_enabled' => true,
                'usage_profile' => 'dual',
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('data.model', 'HiAce GL')
            ->assertJsonPath('data.rental_enabled', true);

        $ownershipResponse = $this->withHeaders($headers)
            ->postJson('/api/vehicle/vehicles/' . $vehicleId . '/ownerships', [
                'ownership_type' => 'own',
                'owner_type' => 'company',
                'owner_name' => 'Internal Company',
                'ownership_role' => 'legal_owner',
                'start_date' => '2026-05-01',
                'is_current' => true,
                'notes' => 'Company-owned test vehicle.',
            ]);

        $ownershipResponse
            ->assertCreated()
            ->assertJsonPath('data.vehicle_id', $vehicleId)
            ->assertJsonPath('data.ownership_type', 'own')
            ->assertJsonPath('data.owner_type', 'company');

        $ownershipId = (int) $ownershipResponse->json('data.id');

        $this->withHeaders($headers)
            ->getJson('/api/vehicle/vehicles/' . $vehicleId . '/ownerships/current?ownership_role=legal_owner')
            ->assertOk()
            ->assertJsonPath('data.id', $ownershipId);

        $this->withHeaders($headers)
            ->getJson('/api/vehicle/vehicles/' . $vehicleId . '/validate/service')
            ->assertOk()
            ->assertJsonPath('data.is_valid', true);

        $this->withHeaders($headers)
            ->getJson('/api/vehicle/vehicles/' . $vehicleId . '/validate/rental')
            ->assertOk()
            ->assertJsonPath('data.is_valid', true);
    }

    public function test_vehicle_create_returns_validation_errors(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/vehicle/vehicles', ['year' => 99, 'current_odometer' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['year', 'current_odometer']);
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
}
