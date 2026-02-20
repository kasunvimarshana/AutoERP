<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\IdempotencyKey;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        Permission::firstOrCreate(['name' => 'orders.create', 'guard_name' => 'api']);
        $this->user->givePermissionTo(
            Permission::where('name', 'orders.create')->where('guard_name', 'api')->first()
        );
    }

    public function test_request_without_idempotency_key_passes_through(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/orders', [
                'type' => 'sale',
                'currency' => 'USD',
            ]);

        $response->assertStatus(201);
        $this->assertFalse($response->headers->has('Idempotency-Key'));
    }

    public function test_first_request_with_idempotency_key_is_processed(): void
    {
        $key = 'test-key-'.uniqid();

        $response = $this->actingAs($this->user, 'api')
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/api/v1/orders', [
                'type' => 'sale',
                'currency' => 'USD',
            ]);

        $response->assertStatus(201);
        $this->assertSame($key, $response->headers->get('Idempotency-Key'));
        $this->assertFalse($response->headers->has('X-Idempotent-Replayed'));

        $this->assertDatabaseHas('idempotency_keys', [
            'user_id' => $this->user->id,
            'idempotency_key' => $key,
        ]);
    }

    public function test_duplicate_request_returns_cached_response(): void
    {
        $key = 'replay-key-'.uniqid();

        // First request
        $first = $this->actingAs($this->user, 'api')
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/api/v1/orders', [
                'type' => 'sale',
                'currency' => 'USD',
            ]);

        $first->assertStatus(201);
        $firstOrderId = $first->json('data.id');

        // Second request with the same key — must replay the original response
        $second = $this->actingAs($this->user, 'api')
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/api/v1/orders', [
                'type' => 'sale',
                'currency' => 'USD',
            ]);

        $second->assertStatus(201);
        $this->assertSame('true', $second->headers->get('X-Idempotent-Replayed'));

        // The replayed response must contain the same order id as the first
        $this->assertSame($firstOrderId, $second->json('data.id'));

        // Only one order was created
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_different_keys_create_separate_resources(): void
    {
        $key1 = 'unique-key-a-'.uniqid();
        $key2 = 'unique-key-b-'.uniqid();

        $this->actingAs($this->user, 'api')
            ->withHeaders(['Idempotency-Key' => $key1])
            ->postJson('/api/v1/orders', ['type' => 'sale', 'currency' => 'USD'])
            ->assertStatus(201);

        $this->actingAs($this->user, 'api')
            ->withHeaders(['Idempotency-Key' => $key2])
            ->postJson('/api/v1/orders', ['type' => 'sale', 'currency' => 'USD'])
            ->assertStatus(201);

        $this->assertDatabaseCount('orders', 2);
    }

    public function test_idempotency_key_exceeding_max_length_is_rejected(): void
    {
        $longKey = str_repeat('x', 256);

        $this->actingAs($this->user, 'api')
            ->withHeaders(['Idempotency-Key' => $longKey])
            ->postJson('/api/v1/orders', ['type' => 'sale'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Idempotency-Key must not exceed 255 characters.');
    }

    public function test_get_requests_are_never_idempotency_checked(): void
    {
        $key = 'get-key-'.uniqid();

        // GET requests should always pass through; no record should be stored
        $this->actingAs($this->user, 'api')
            ->withHeaders(['Idempotency-Key' => $key])
            ->getJson('/api/v1/orders')
            ->assertStatus(200);

        $this->assertDatabaseMissing('idempotency_keys', [
            'idempotency_key' => $key,
        ]);
    }

    public function test_expired_key_is_treated_as_new(): void
    {
        $key = 'expired-key-'.uniqid();

        // Seed an already-expired (and processed) record
        IdempotencyKey::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'idempotency_key' => $key,
            'request_method' => 'POST',
            'request_path' => 'api/v1/orders',
            'response_status' => 201,
            'response_body' => json_encode(['data' => ['id' => 'old-id']]),
            'processed_at' => now()->subHours(25),
            'expires_at' => now()->subHour(), // expired
        ]);

        // A new request with this key should be treated as fresh
        $response = $this->actingAs($this->user, 'api')
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/api/v1/orders', ['type' => 'sale', 'currency' => 'USD'])
            ->assertStatus(201);

        // Should NOT be replayed (new processing)
        $this->assertFalse($response->headers->has('X-Idempotent-Replayed'));
    }
}
