<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\CRM\Models\Customer;
use App\Modules\Inventory\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Tenant 1',
            'slug' => 'tenant-1',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Tenant 2',
            'slug' => 'tenant-2',
        ]);

        $this->user1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'email' => 'user1@tenant1.com',
        ]);

        $this->user2 = User::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'email' => 'user2@tenant2.com',
        ]);
    }

    public function test_user_can_only_see_their_tenant_customers(): void
    {
        Customer::factory()->count(3)->create(['tenant_id' => $this->tenant1->id]);
        Customer::factory()->count(2)->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_access_another_tenant_customer(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(404);
    }

    public function test_user_cannot_update_another_tenant_customer(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->putJson("/api/v1/customers/{$customer->id}", ['phone' => '+9999999999']);

        $response->assertStatus(404);
    }

    public function test_user_cannot_delete_another_tenant_customer(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_user_can_only_see_their_tenant_products(): void
    {
        Product::factory()->count(4)->create(['tenant_id' => $this->tenant1->id]);
        Product::factory()->count(3)->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    }

    public function test_user_cannot_access_another_tenant_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(404);
    }

    public function test_tenant_data_is_completely_isolated(): void
    {
        $customer1 = Customer::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'email' => 'same@email.com'
        ]);

        $customer2 = Customer::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'email' => 'same@email.com'
        ]);

        $response1 = $this->actingAs($this->user1, 'sanctum')
            ->getJson('/api/v1/customers');

        $response2 = $this->actingAs($this->user2, 'sanctum')
            ->getJson('/api/v1/customers');

        $response1->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $response2->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->assertNotEquals(
            $response1->json('data.0.id'),
            $response2->json('data.0.id')
        );
    }

    public function test_search_respects_tenant_isolation(): void
    {
        Customer::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'first_name' => 'John',
        ]);

        Customer::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'first_name' => 'John',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->getJson('/api/v1/customers/search?q=John');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
