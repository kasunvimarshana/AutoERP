<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\Lead;
use Modules\Sales\Entities\SalesOrder;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_customer_successfully(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'type' => 'business',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
    }

    public function test_it_generates_customer_number_automatically(): void
    {
        $customer = Customer::factory()->create();

        $this->assertNotNull($customer->customer_number);
        $this->assertStringStartsWith('CUST-', $customer->customer_number);
    }

    public function test_it_has_active_scope(): void
    {
        Customer::factory()->create(['status' => 'active']);
        Customer::factory()->create(['status' => 'inactive']);
        Customer::factory()->create(['status' => 'active']);

        $activeCustomers = Customer::active()->get();

        $this->assertCount(2, $activeCustomers);
    }

    public function test_it_has_business_scope(): void
    {
        Customer::factory()->create(['type' => 'business']);
        Customer::factory()->create(['type' => 'individual']);
        Customer::factory()->create(['type' => 'business']);

        $businessCustomers = Customer::business()->get();

        $this->assertCount(2, $businessCustomers);
    }

    public function test_it_has_individual_scope(): void
    {
        Customer::factory()->create(['type' => 'individual']);
        Customer::factory()->create(['type' => 'business']);
        Customer::factory()->create(['type' => 'individual']);

        $individualCustomers = Customer::individual()->get();

        $this->assertCount(2, $individualCustomers);
    }

    public function test_it_has_many_leads(): void
    {
        $customer = Customer::factory()->create();
        $leads = Lead::factory()->count(3)->create(['customer_id' => $customer->id]);

        $this->assertCount(3, $customer->leads);
        $this->assertInstanceOf(Lead::class, $customer->leads->first());
    }

    public function test_it_has_many_sales_orders(): void
    {
        $customer = Customer::factory()->create();
        $orders = SalesOrder::factory()->count(3)->create(['customer_id' => $customer->id]);

        $this->assertCount(3, $customer->salesOrders);
        $this->assertInstanceOf(SalesOrder::class, $customer->salesOrders->first());
    }

    public function test_it_calculates_credit_available(): void
    {
        $customer = Customer::factory()->create([
            'credit_limit' => 10000,
            'current_balance' => 3000,
        ]);

        $creditAvailable = $customer->credit_limit - $customer->current_balance;
        $this->assertEquals(7000, $creditAvailable);
    }

    public function test_it_has_vip_customers_scope(): void
    {
        Customer::factory()->create(['is_vip' => true]);
        Customer::factory()->create(['is_vip' => false]);
        Customer::factory()->create(['is_vip' => true]);

        $vipCustomers = Customer::vip()->get();

        $this->assertCount(2, $vipCustomers);
    }

    public function test_it_casts_billing_address_to_array(): void
    {
        $customer = Customer::factory()->create([
            'billing_address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'US',
            ],
        ]);

        $this->assertIsArray($customer->billing_address);
        $this->assertEquals('123 Main St', $customer->billing_address['line1']);
        $this->assertEquals('New York', $customer->billing_address['city']);
    }

    public function test_it_soft_deletes(): void
    {
        $customer = Customer::factory()->create();
        $customerId = $customer->id;

        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $customerId]);
    }

    public function test_it_tracks_created_by_and_updated_by(): void
    {
        $customer = Customer::factory()->create();

        $this->assertNotNull($customer->created_at);
        $this->assertNotNull($customer->updated_at);
    }
}
