<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Invoice\Models\Invoice;
use Modules\Organization\Models\Branch;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Invoice API Feature Test
 *
 * Tests Invoice API endpoints
 */
class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Vehicle $vehicle;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'user']);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->customer = Customer::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);
        $this->branch = Branch::factory()->create();
    }

    /**
     * Test can list invoices
     */
    public function test_can_list_invoices(): void
    {
        Invoice::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'invoice_number',
                        'status',
                        'total_amount',
                    ],
                ],
            ]);
    }

    /**
     * Test can create invoice
     */
    public function test_can_create_invoice(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 10,
            'items' => [
                [
                    'item_type' => 'labor',
                    'description' => 'Oil change',
                    'quantity' => 1,
                    'unit_price' => 50,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/invoices', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'invoice_number',
                    'total_amount',
                ],
            ]);

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
        ]);
    }

    /**
     * Test can show invoice
     */
    public function test_can_show_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
            ]);
    }

    /**
     * Test can update invoice
     */
    public function test_can_update_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $updateData = [
            'notes' => 'Updated notes',
            'status' => 'sent',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/invoices/{$invoice->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'notes' => 'Updated notes',
            'status' => 'sent',
        ]);
    }

    /**
     * Test can delete invoice
     */
    public function test_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/invoices/{$invoice->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('invoices', [
            'id' => $invoice->id,
        ]);
    }

    /**
     * Test can get overdue invoices
     */
    public function test_can_get_overdue_invoices(): void
    {
        Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'due_date' => now()->subDays(5),
            'balance' => 100,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/invoices/overdue/list');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }
}
