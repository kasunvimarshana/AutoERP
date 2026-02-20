<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceSchemeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_unauthenticated_cannot_list_invoice_schemes(): void
    {
        $this->getJson('/api/v1/invoice-schemes')->assertStatus(401);
    }

    public function test_user_with_permission_can_list_invoice_schemes(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'invoice_schemes.view', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/invoice-schemes')
            ->assertStatus(200);
    }

    public function test_user_can_create_invoice_scheme(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'invoice_schemes.create', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/invoice-schemes', [
                'name' => 'Sales Invoice',
                'scheme_type' => 'sell',
                'prefix' => 'INV-',
                'start_number' => 1,
                'number_of_digits' => 5,
                'is_default' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.prefix', 'INV-')
            ->assertJsonPath('data.is_default', true);
    }

    public function test_scheme_format_method_pads_correctly(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'invoice_schemes.create', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/invoice-schemes', [
                'name' => 'Purchase Order',
                'scheme_type' => 'purchase',
                'prefix' => 'PO-',
                'suffix' => '-2024',
                'start_number' => 1,
                'number_of_digits' => 4,
            ]);

        $response->assertStatus(201);

        // Test the format method via the model directly
        $scheme = \App\Models\InvoiceScheme::find($response->json('data.id'));
        $this->assertSame('PO-0042-2024', $scheme->format(42));
    }

    public function test_creating_second_default_demotes_first(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'invoice_schemes.create', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $first = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/invoice-schemes', [
                'name' => 'First',
                'scheme_type' => 'sell',
                'prefix' => 'A-',
                'is_default' => true,
            ])->json('data.id');

        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/invoice-schemes', [
                'name' => 'Second',
                'scheme_type' => 'sell',
                'prefix' => 'B-',
                'is_default' => true,
            ]);

        // First should no longer be default
        $this->assertDatabaseHas('invoice_schemes', ['id' => $first, 'is_default' => false]);
    }

    public function test_unauthenticated_cannot_create_invoice_scheme(): void
    {
        $this->postJson('/api/v1/invoice-schemes', ['name' => 'Test'])
            ->assertStatus(401);
    }
}
