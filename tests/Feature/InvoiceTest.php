<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Billing\Models\Invoice;
use App\Modules\CRM\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_create_invoice(): void
    {
        $invoiceData = [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 1000.00,
            'tax_amount' => 100.00,
            'discount_amount' => 50.00,
            'total_amount' => 1050.00,
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/invoices', $invoiceData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'total_amount' => 1050.00,
        ]);
    }

    public function test_can_list_invoices(): void
    {
        Invoice::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'invoice_number', 'customer_id', 'total_amount']
                ],
                'meta' => ['current_page', 'per_page', 'total']
            ]);
    }

    public function test_can_get_single_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['invoice_number' => $invoice->invoice_number]);
    }

    public function test_can_update_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        $updateData = [
            'status' => 'issued',
            'notes' => 'Updated notes',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/invoices/{$invoice->id}", $updateData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'issued',
            'notes' => 'Updated notes',
        ]);
    }

    public function test_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/invoices/{$invoice->id}");

        $response->assertStatus(200);
        
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_can_filter_invoices_by_status(): void
    {
        Invoice::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'issued',
        ]);

        Invoice::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/invoices?status=issued');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_invoices_by_customer(): void
    {
        $otherCustomer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        Invoice::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        Invoice::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/invoices?customer_id={$this->customer->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_invoice_calculates_totals_correctly(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'subtotal' => 1000.00,
            'tax_amount' => 100.00,
            'discount_amount' => 50.00,
        ]);

        $expectedTotal = 1000.00 + 100.00 - 50.00;
        
        $this->assertEquals($expectedTotal, $invoice->total_amount);
    }

    public function test_can_mark_invoice_as_paid(): void
    {
        $invoice = Invoice::factory()->issued()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 1050.00,
        ]);

        $updateData = [
            'status' => 'paid',
            'paid_amount' => 1050.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/invoices/{$invoice->id}", $updateData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
            'paid_amount' => 1050.00,
        ]);
    }

    public function test_can_handle_partial_payment(): void
    {
        $invoice = Invoice::factory()->partiallyPaid()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 1000.00,
            'paid_amount' => 500.00,
        ]);

        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(500.00, $invoice->paid_amount);
    }

    public function test_tenant_isolation_works(): void
    {
        $otherTenant = Tenant::factory()->create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => 'active',
        ]);

        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $otherInvoice = Invoice::factory()->create([
            'tenant_id' => $otherTenant->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/invoices/{$otherInvoice->id}");

        $response->assertStatus(404);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/invoices');

        $response->assertStatus(401);
    }

    public function test_can_search_invoices(): void
    {
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-12345',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/invoices/search?q=INV-12345');

        $response->assertStatus(200)
            ->assertJsonFragment(['invoice_number' => 'INV-12345']);
    }

    public function test_overdue_invoices_are_tracked(): void
    {
        $overdueInvoice = Invoice::factory()->overdue()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->assertEquals('overdue', $overdueInvoice->status);
        $this->assertTrue($overdueInvoice->due_date->isPast());
    }
}
