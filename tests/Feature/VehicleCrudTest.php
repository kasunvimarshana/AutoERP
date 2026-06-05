<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class VehicleCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $login = $this->postJson('/api/auth/login', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'provider_key' => 'internal',
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'device_name' => 'Vehicle feature test',
        ])->assertOk();

        $this->headers = [
            'Authorization' => 'Bearer '.$login->json('data.tokens.access_token'),
            'X-Tenant-ID' => '1',
            'X-Organization-Unit-ID' => '1',
        ];
    }

    public function test_vehicle_crud_is_tenant_scoped_searchable_and_soft_deleted(): void
    {
        $created = $this->withHeaders($this->headers)
            ->postJson('/api/vehicle/vehicles', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.vehicle_code', 'VEH-100')
            ->assertJsonPath('data.registration_number', 'CAB-1234')
            ->assertJsonPath('data.make', 'Toyota')
            ->assertJsonPath('data.status', 'active');

        $vehicleId = (int) $created->json('data.id');

        $this->withHeaders($this->headers)
            ->postJson('/api/vehicle/vehicles', [
                ...$this->payload(),
                'registration_number' => 'CAB-9999',
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['vehicle_code']]);

        $this->withHeaders($this->headers)
            ->postJson('/api/vehicle/vehicles', [
                ...$this->payload(),
                'vehicle_code' => 'VEH-101',
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['registration_number']]);

        $this->withHeaders($this->headers)
            ->postJson('/api/vehicle/vehicles', [
                ...$this->payload(),
                'vehicle_code' => 'VEH-INVALID',
                'registration_number' => 'CAB-0000',
                'year' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['year']]);

        $this->withHeaders($this->headers)
            ->getJson('/api/vehicle/vehicles?search=Toyota&status=active&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.notes');

        $this->withHeaders($this->headers)
            ->patchJson('/api/vehicle/vehicles/'.$vehicleId, [
                'model' => 'Corolla Cross',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.model', 'Corolla Cross')
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.row_version', 2);

        $foreignVehicleId = DB::table('vehicles')->insertGetId([
            'tenant_id' => $this->createOtherTenant(),
            'organization_unit_id' => null,
            'vehicle_code' => 'VEH-FOREIGN',
            'registration_number' => 'FOREIGN-1',
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders($this->headers)
            ->getJson('/api/vehicle/vehicles/'.$foreignVehicleId)
            ->assertNotFound();

        $this->withHeaders($this->headers)
            ->deleteJson('/api/vehicle/vehicles/'.$vehicleId)
            ->assertNoContent();

        $this->assertSoftDeleted('vehicles', ['id' => $vehicleId, 'tenant_id' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'vehicle_code' => 'VEH-100',
            'registration_number' => 'CAB-1234',
            'chassis_number' => 'JTDBR32E720123456',
            'engine_number' => 'ENG-123456',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'color' => 'White',
            'vehicle_type' => 'Car',
            'fuel_type' => 'Petrol',
            'transmission_type' => 'Automatic',
            'ownership_type' => 'Company',
            'status' => 'active',
            'notes' => 'Pool vehicle.',
        ];
    }

    private function createOtherTenant(): int
    {
        $tenant = (array) DB::table('tenants')->where('id', 1)->first();
        unset($tenant['id']);

        $tenant['code'] = 'VEH-OTHER';
        $tenant['name'] = 'Vehicle Other Tenant';
        $tenant['slug'] = 'vehicle-other';
        $tenant['uuid'] = (string) Str::uuid();
        $tenant['isolation_key'] = 'vehicle-other';
        $tenant['created_at'] = now();
        $tenant['updated_at'] = now();

        return (int) DB::table('tenants')->insertGetId($tenant);
    }
}
