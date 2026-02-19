<?php

declare(strict_types=1);

namespace Modules\Customer\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Customer\Services\VehicleService;
use Tests\TestCase;

/**
 * Vehicle Service Unit Test
 *
 * Tests business logic in VehicleService
 */
class VehicleServiceTest extends TestCase
{
    use RefreshDatabase;

    private VehicleService $vehicleService;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vehicleService = app(VehicleService::class);
        $this->customer = Customer::factory()->create();
    }

    /**
     * Test can create vehicle
     */
    public function test_can_create_vehicle(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'registration_number' => 'ABC123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'current_mileage' => 5000,
            'status' => 'active',
        ];

        $vehicle = $this->vehicleService->create($data);

        $this->assertInstanceOf(Vehicle::class, $vehicle);
        $this->assertEquals('Toyota', $vehicle->make);
        $this->assertNotNull($vehicle->vehicle_number);
    }

    /**
     * Test generates unique vehicle number
     */
    public function test_generates_unique_vehicle_number(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'registration_number' => 'XYZ789',
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2023,
            'current_mileage' => 1000,
            'status' => 'active',
        ];

        $vehicle = $this->vehicleService->create($data);

        $this->assertNotNull($vehicle->vehicle_number);
        $this->assertStringStartsWith('VEH-', $vehicle->vehicle_number);
    }

    /**
     * Test can update vehicle
     */
    public function test_can_update_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'make' => 'Toyota',
        ]);

        $updated = $this->vehicleService->update($vehicle->id, [
            'make' => 'Honda',
        ]);

        $this->assertEquals('Honda', $updated->make);
    }

    /**
     * Test can update vehicle mileage
     */
    public function test_can_update_vehicle_mileage(): void
    {
        $vehicle = Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'current_mileage' => 5000,
        ]);

        $updated = $this->vehicleService->updateMileage($vehicle->id, 6000);

        $this->assertEquals(6000, $updated->current_mileage);
    }

    /**
     * Test cannot decrease mileage
     */
    public function test_cannot_decrease_mileage(): void
    {
        $vehicle = Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'current_mileage' => 5000,
        ]);

        $this->expectException(\Exception::class);
        $this->vehicleService->updateMileage($vehicle->id, 4000);
    }

    /**
     * Test can transfer vehicle ownership
     */
    public function test_can_transfer_vehicle_ownership(): void
    {
        $vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);
        $newCustomer = Customer::factory()->create();

        $transferred = $this->vehicleService->transferOwnership($vehicle->id, $newCustomer->id);

        $this->assertEquals($newCustomer->id, $transferred->customer_id);
    }

    /**
     * Test can get vehicles by customer
     */
    public function test_can_get_vehicles_by_customer(): void
    {
        Vehicle::factory()->count(3)->create(['customer_id' => $this->customer->id]);

        $vehicles = $this->vehicleService->getByCustomer($this->customer->id);

        $this->assertCount(3, $vehicles);
    }

    /**
     * Test can get vehicles due for service
     */
    public function test_can_get_vehicles_due_for_service(): void
    {
        // Create vehicle due for service
        Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'next_service_date' => now()->subDays(5), // Overdue
            'status' => 'active',
        ]);

        // Create vehicle not due
        Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'next_service_date' => now()->addDays(30),
            'status' => 'active',
        ]);

        $dueVehicles = $this->vehicleService->getDueForService();

        $this->assertEquals(1, $dueVehicles->count());
    }

    /**
     * Test can get vehicles with expiring insurance
     */
    public function test_can_get_vehicles_with_expiring_insurance(): void
    {
        // Create vehicle with expiring insurance (within 30 days)
        Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'insurance_expiry' => now()->addDays(15),
            'status' => 'active',
        ]);

        // Create vehicle with valid insurance (beyond 30 days)
        Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'insurance_expiry' => now()->addMonths(6),
            'status' => 'active',
        ]);

        $expiringVehicles = $this->vehicleService->getWithExpiringInsurance(30);

        $this->assertEquals(1, $expiringVehicles->count());
    }

    /**
     * Test can get vehicle statistics
     */
    public function test_can_get_vehicle_statistics(): void
    {
        $vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);

        $stats = $this->vehicleService->getServiceStatistics($vehicle->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_services', $stats);
        $this->assertArrayHasKey('total_cost', $stats);
        $this->assertArrayHasKey('current_mileage', $stats);
    }

    /**
     * Test can soft delete vehicle
     */
    public function test_can_soft_delete_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);

        $this->vehicleService->delete($vehicle->id);

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }
}
