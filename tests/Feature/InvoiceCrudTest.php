<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $createPerm = Permission::firstOrCreate(['name' => 'invoices.create', 'guard_name' => 'api']);
        $sendPerm = Permission::firstOrCreate(['name' => 'invoices.send', 'guard_name' => 'api']);
        $voidPerm = Permission::firstOrCreate(['name' => 'invoices.void', 'guard_name' => 'api']);

        $this->admin->givePermissionTo([$createPerm, $sendPerm, $voidPerm]);
    }

    public function test_admin_can_create_invoice(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/invoices', [
                'currency' => 'USD',
                'items' => [
                    [
                        'description' => 'Consulting Services',
                        'quantity' => '5',
                        'unit_price' => '100.00',
                        'tax_rate' => '10',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', InvoiceStatus::Draft->value)
            ->assertJsonStructure(['data' => ['id', 'invoice_number', 'status', 'subtotal', 'tax_amount', 'total', 'amount_due']]);
    }

    public function test_user_without_permission_cannot_create_invoice(): void
    {
        $noPermUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($noPermUser, 'api')
            ->postJson('/api/v1/invoices', ['currency' => 'USD'])
            ->assertStatus(403);
    }

    public function test_invoice_totals_are_correctly_calculated(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/invoices', [
                'currency' => 'USD',
                'items' => [
                    [
                        'description' => 'Product A',
                        'quantity' => '3',
                        'unit_price' => '50.00',
                        'tax_rate' => '10',
                    ],
                    [
                        'description' => 'Product B',
                        'quantity' => '2',
                        'unit_price' => '25.00',
                        'tax_rate' => '0',
                    ],
                ],
            ]);

        $response->assertStatus(201);

        // subtotal = (3 * 50) + (2 * 25) = 200
        $this->assertEquals(200, (float) $response->json('data.subtotal'));
        // tax = (150 * 10/100) + 0 = 15
        $this->assertEquals(15, (float) $response->json('data.tax_amount'));
        // total = 200 + 15 = 215
        $this->assertEquals(215, (float) $response->json('data.total'));
        // amount_due initially equals total
        $this->assertEquals(215, (float) $response->json('data.amount_due'));
    }

    public function test_admin_can_send_draft_invoice(): void
    {
        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/invoices', [
                'currency' => 'USD',
                'items' => [
                    [
                        'description' => 'Service',
                        'quantity' => '1',
                        'unit_price' => '200.00',
                    ],
                ],
            ]);

        $invoiceId = $createResponse->json('data.id');

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/invoices/{$invoiceId}/send")
            ->assertStatus(200)
            ->assertJsonPath('data.status', InvoiceStatus::Sent->value);
    }

    public function test_cannot_send_already_sent_invoice(): void
    {
        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/invoices', [
                'currency' => 'USD',
                'items' => [
                    ['description' => 'Service', 'quantity' => '1', 'unit_price' => '100.00'],
                ],
            ]);

        $invoiceId = $createResponse->json('data.id');

        // First send succeeds
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/invoices/{$invoiceId}/send")
            ->assertStatus(200);

        // Second send should fail
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/invoices/{$invoiceId}/send")
            ->assertStatus(500);
    }

    public function test_admin_can_void_invoice(): void
    {
        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/invoices', [
                'currency' => 'USD',
                'items' => [
                    ['description' => 'Item', 'quantity' => '1', 'unit_price' => '50.00'],
                ],
            ]);

        $invoiceId = $createResponse->json('data.id');

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/invoices/{$invoiceId}/void")
            ->assertStatus(200)
            ->assertJsonPath('data.status', InvoiceStatus::Void->value);
    }

    public function test_invoice_item_description_is_required(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/invoices', [
                'currency' => 'USD',
                'items' => [
                    ['quantity' => '1', 'unit_price' => '50.00'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.description']);
    }
}
