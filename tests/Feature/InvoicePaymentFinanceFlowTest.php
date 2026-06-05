<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InvoicePaymentFinanceFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $login = $this->postJson('/api/auth/login', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'provider_key' => 'internal',
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'device_name' => 'Invoice payment test',
        ])->assertOk();

        $this->headers = [
            'Authorization' => 'Bearer '.$login->json('data.tokens.access_token'),
            'X-Tenant-ID' => '1',
            'X-Organization-Unit-ID' => '1',
        ];
    }

    public function test_invoice_payment_finance_and_credit_note_flow(): void
    {
        $customerId = $this->customerId();
        $paymentMethodId = (int) DB::table('payment_methods')->where('tenant_id', 1)->where('code', 'CASH')->value('id');

        $created = $this->withHeaders($this->headers)
            ->postJson('/api/invoice/invoices', [
                'invoice_number' => 'INV-TEST-100',
                'document_type' => 'invoice',
                'business_context' => 'sales',
                'ledger_direction' => 'receivable',
                'balance_effect' => 'increase',
                'customer_id' => $customerId,
                'invoice_date' => '2026-06-06',
                'due_date' => '2026-06-20',
                'lines' => [[
                    'description' => 'Workshop service',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount_total' => 5,
                    'tax_total' => 20,
                    'charge_total' => 3,
                ]],
                'adjustments' => [[
                    'effect' => 'deduct',
                    'adjustment_type' => 'discount',
                    'amount' => 10,
                ], [
                    'effect' => 'add',
                    'adjustment_type' => 'tax',
                    'amount' => 2,
                ], [
                    'effect' => 'add',
                    'adjustment_type' => 'charge',
                    'amount' => 4,
                ], [
                    'effect' => 'add',
                    'adjustment_type' => 'debit_adjustment',
                    'amount' => 6,
                ], [
                    'effect' => 'deduct',
                    'adjustment_type' => 'credit_adjustment',
                    'amount' => 1,
                ]],
                'rounding_adjustment' => 0.5,
            ])
            ->assertCreated()
            ->assertJsonPath('data.gross_total', '200.0000')
            ->assertJsonPath('data.line_discount_total', '5.0000')
            ->assertJsonPath('data.header_discount_total', '10.0000')
            ->assertJsonPath('data.tax_total', '22.0000')
            ->assertJsonPath('data.charge_total', '7.0000')
            ->assertJsonPath('data.debit_adjustment_total', '6.0000')
            ->assertJsonPath('data.credit_adjustment_total', '1.0000')
            ->assertJsonPath('data.grand_total', '219.5000')
            ->assertJsonPath('data.balance_due', '219.5000');

        $invoiceId = (int) $created->json('data.id');

        $this->withHeaders($this->headers)
            ->postJson("/api/invoice/invoices/$invoiceId/issue")
            ->assertOk()
            ->assertJsonPath('data.status', 'issued');

        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => 1,
            'source_module' => 'invoice',
            'source_reference' => 'INV-TEST-100',
            'total_debit' => 219.5,
            'total_credit' => 219.5,
        ]);
        $this->assertDatabaseHas('ar_transactions', [
            'tenant_id' => 1,
            'source_reference' => 'INV-TEST-100',
            'outstanding_amount' => 219.5,
        ]);

        $payment = $this->withHeaders($this->headers)
            ->postJson('/api/payment/payments', [
                'payment_number' => 'PAY-TEST-100',
                'party_type' => 'customer',
                'party_id' => $customerId,
                'payment_date' => '2026-06-07',
                'amount' => 100,
                'direction' => 'inbound',
                'payment_method_id' => $paymentMethodId,
                'allocations' => [[
                    'invoice_id' => $invoiceId,
                    'allocated_amount' => 100,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.allocated_amount', '100.0000');

        $this->withHeaders($this->headers)
            ->getJson("/api/invoice/invoices/$invoiceId")
            ->assertOk()
            ->assertJsonPath('data.status', 'partially_paid')
            ->assertJsonPath('data.balance_due', '119.5000');

        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => 1,
            'source_module' => 'payment',
            'source_reference' => 'PAY-TEST-100',
            'total_debit' => 100,
            'total_credit' => 100,
        ]);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => (int) $payment->json('data.id'),
            'invoice_id' => $invoiceId,
            'allocated_amount' => 100,
        ]);

        $credit = $this->withHeaders($this->headers)
            ->postJson('/api/invoice/invoices', [
                'invoice_number' => 'CN-TEST-100',
                'document_type' => 'credit_adjustment',
                'business_context' => 'sales',
                'ledger_direction' => 'receivable',
                'balance_effect' => 'decrease',
                'customer_id' => $customerId,
                'original_invoice_id' => $invoiceId,
                'invoice_date' => '2026-06-08',
                'lines' => [[
                    'description' => 'Goodwill credit',
                    'quantity' => 1,
                    'unit_price' => 119.5,
                ]],
            ])
            ->assertCreated();

        $this->withHeaders($this->headers)
            ->postJson('/api/invoice/invoices/'.((int) $credit->json('data.id')).'/issue')
            ->assertOk()
            ->assertJsonPath('data.status', 'credited');

        $this->withHeaders($this->headers)
            ->getJson("/api/invoice/invoices/$invoiceId")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.balance_due', '0.0000');

        $this->withHeaders($this->headers)
            ->getJson('/api/finance/journal-entries?search=INV-TEST-100')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    private function customerId(): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'customer_code' => 'CUS-FLOW-100',
            'customer_name' => 'Flow Customer',
            'customer_type' => 'business',
            'credit_limit' => 10000,
            'payment_terms_days' => 30,
            'status' => 'active',
            'is_active' => true,
            'credit_hold' => false,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
