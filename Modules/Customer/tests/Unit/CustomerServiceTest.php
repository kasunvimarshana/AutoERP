<?php

declare(strict_types=1);

namespace Modules\Customer\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerService;
use Tests\TestCase;

/**
 * Customer Service Unit Test
 *
 * Tests business logic in CustomerService
 */
class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerService $customerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerService = app(CustomerService::class);
    }

    /**
     * Test service can create a customer with valid data
     */
    public function test_can_create_customer_with_valid_data(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'customer_type' => 'individual',
            'status' => 'active',
        ];

        $customer = $this->customerService->create($data);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('John', $customer->first_name);
        $this->assertEquals('john@example.com', $customer->email);
        $this->assertNotNull($customer->customer_number);
    }

    /**
     * Test service generates unique customer number
     */
    public function test_generates_unique_customer_number(): void
    {
        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'customer_type' => 'individual',
            'status' => 'active',
        ];

        $customer = $this->customerService->create($data);

        $this->assertNotNull($customer->customer_number);
        $this->assertStringStartsWith('CUST-', $customer->customer_number);
    }

    /**
     * Test service prevents duplicate email
     */
    public function test_prevents_duplicate_email(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'duplicate@example.com',
            'customer_type' => 'individual',
            'status' => 'active',
        ];

        // Create first customer
        $this->customerService->create($data);

        // Attempt to create second customer with same email
        $this->expectException(ValidationException::class);
        $this->customerService->create($data);
    }

    /**
     * Test service can update customer
     */
    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);

        $updated = $this->customerService->update($customer->id, [
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
        ]);

        $this->assertEquals('Jane', $updated->first_name);
        $this->assertEquals('jane@example.com', $updated->email);
    }

    /**
     * Test service can get customer with vehicles
     */
    public function test_can_get_customer_with_vehicles(): void
    {
        $customer = Customer::factory()
            ->hasVehicles(2)
            ->create();

        $result = $this->customerService->getWithVehicles($customer->id);

        $this->assertNotNull($result);
        $this->assertEquals($customer->id, $result->id);
        $this->assertCount(2, $result->vehicles);
    }

    /**
     * Test service can search customers
     */
    public function test_can_search_customers(): void
    {
        Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john@example.com',
        ]);

        Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]);

        $results = $this->customerService->search('John');

        $this->assertGreaterThan(0, $results->count());
        $this->assertEquals('John', $results->first()->first_name);
    }

    /**
     * Test service can get customers by type
     */
    public function test_can_get_customers_by_type(): void
    {
        Customer::factory()->count(2)->create(['customer_type' => 'individual']);
        Customer::factory()->count(3)->create(['customer_type' => 'business']);

        $individuals = $this->customerService->getByType('individual');
        $businesses = $this->customerService->getByType('business');

        $this->assertEquals(2, $individuals->count());
        $this->assertEquals(3, $businesses->count());
    }

    /**
     * Test service can get active customers
     */
    public function test_can_get_active_customers(): void
    {
        Customer::factory()->count(2)->create(['status' => 'active']);
        Customer::factory()->create(['status' => 'inactive']);

        $active = $this->customerService->getActive();

        $this->assertEquals(2, $active->count());
    }

    /**
     * Test service can soft delete customer
     */
    public function test_can_soft_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->customerService->delete($customer->id);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    /**
     * Test service can get customer statistics
     */
    public function test_can_get_customer_statistics(): void
    {
        $customer = Customer::factory()
            ->hasVehicles(2)
            ->create();

        $stats = $this->customerService->getStatistics($customer->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_vehicles', $stats);
        $this->assertArrayHasKey('total_service_records', $stats);
        $this->assertEquals(2, $stats['total_vehicles']);
    }
}
