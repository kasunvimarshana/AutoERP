<?php

namespace Tests\Unit\Modules\CustomerManagement;

use Tests\TestCase;
use App\Modules\CustomerManagement\Models\Customer;
use App\Modules\CustomerManagement\Repositories\CustomerRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CustomerRepository(new Customer());
    }

    public function test_can_create_customer(): void
    {
        $data = [
            'customer_type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
        ];

        $customer = $this->repository->create($data);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('John', $customer->first_name);
        $this->assertEquals('Doe', $customer->last_name);
        $this->assertNotNull($customer->customer_code);
        $this->assertNotNull($customer->uuid);
    }

    public function test_can_find_customer_by_email(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        $found = $this->repository->findByEmail('test@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($customer->id, $found->id);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create();

        $updated = $this->repository->update($customer->id, [
            'first_name' => 'Updated',
        ]);

        $this->assertEquals('Updated', $updated->first_name);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Updated',
        ]);
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $result = $this->repository->delete($customer->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
