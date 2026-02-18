<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Entities\Account;
use Modules\Accounting\Entities\Invoice;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_invoice_with_lines(): void
    {
        $revenueAccount = Account::factory()->create([
            'tenant_id' => 1,
            'type' => 'revenue',
        ]);

        $data = [
            'customer_id' => 1,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'USD',
            'lines' => [
                [
                    'account_id' => $revenueAccount->id,
                    'description' => 'Test Item',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'tax_rate' => 10,
                ],
            ],
        ];

        $response = $this->postJson('/api/accounting/v1/invoices', $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'invoice_number',
                'total_amount',
                'lines',
            ],
        ]);

        $this->assertDatabaseHas('invoices', [
            'customer_id' => 1,
        ]);

        $this->assertDatabaseHas('invoice_lines', [
            'description' => 'Test Item',
            'quantity' => 2,
        ]);
    }

    public function test_it_can_send_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/accounting/v1/invoices/{$invoice->id}/send");

        $response->assertStatus(200);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'sent',
        ]);
    }
}
