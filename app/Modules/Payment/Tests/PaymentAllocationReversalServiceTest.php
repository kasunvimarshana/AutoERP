<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentAllocationReversalService;
use Tests\TestCase;

final class PaymentAllocationReversalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reverses_one_invoice_allocation_and_restores_payment_and_invoice_balances(): void
    {
        $tenantId = $this->createTenant();
        $invoice = $this->withTenantExecutionContext($tenantId, function () use ($tenantId) {
            $invoice = app(InvoiceCreationService::class)->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Manual,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-07-11',
                invoiceNumber: 'INV-ALLOC-REVERSAL',
                lines: [new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Deposit allocation reversal invoice',
                    quantity: '1.000000',
                    unitPrice: '1000.000000',
                )],
            ));
            $statuses = app(InvoiceStatusService::class);
            $invoice = $statuses->transition($invoice, InvoiceStatus::Approved);

            return $statuses->transition($invoice, InvoiceStatus::Posted);
        });

        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceSettlementServiceInterface::class)
            ->applyPaymentAllocation((int) $invoice->getKey(), '400.000000'));

        $paymentMethodId = (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => 'TEST-CASH',
            'name' => 'Test Cash',
            'method_type' => 'cash',
            'direction_allowed' => 'both',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'payment_number' => 'PAY-ALLOC-REVERSAL',
            'payment_type' => PaymentType::Advance->value,
            'direction' => PaymentDirection::Inbound->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'allocation_status' => PaymentAllocationState::FullyAllocated->value,
            'posting_status' => PaymentPostingStatus::Posted->value,
            'payment_date' => '2026-07-11',
            'exchange_rate' => '1.000000',
            'total_amount' => '400.000000',
            'allocated_amount' => '400.000000',
            'unapplied_amount' => '0.000000',
            'refunded_amount' => '0.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'payment_id' => $paymentId,
            'line_number' => 1,
            'payment_method_id' => $paymentMethodId,
            'payment_method_code_snapshot' => 'TEST-CASH',
            'payment_method_name_snapshot' => 'Test Cash',
            'payment_method_type_snapshot' => 'cash',
            'amount' => '400.000000',
            'status' => 'cleared',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_allocations')->insert([
            'tenant_id' => $tenantId,
            'payment_id' => $paymentId,
            'invoice_id' => $invoice->getKey(),
            'invoice_number_snapshot' => $invoice->invoice_number,
            'invoice_date_snapshot' => $invoice->invoice_date?->toDateString(),
            'invoice_total' => '1000.000000',
            'invoice_balance_before' => '1000.000000',
            'previously_allocated_amount' => '0.000000',
            'allocated_amount' => '400.000000',
            'invoice_balance_after' => '600.000000',
            'allocation_date' => '2026-07-11',
            'allocation_method' => 'specific_invoice',
            'status' => AllocationStatus::Active->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payment = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Payment => Payment::query()->findOrFail($paymentId),
        );
        $reversed = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Payment => app(PaymentAllocationReversalService::class)->reverseForInvoice(
                $payment,
                (int) $invoice->getKey(),
                (int) $payment->row_version,
                'Deposit application corrected',
            ),
        );

        $this->assertSame('0.000000', (string) $reversed->allocated_amount);
        $this->assertSame('400.000000', (string) $reversed->unapplied_amount);
        $this->assertSame(
            AllocationStatus::Reversed->value,
            $this->withTenantExecutionContext(
                $tenantId,
                fn (): string => (string) $reversed->allocations()->firstOrFail()->status->value,
            ),
        );
        $this->assertSame(
            '1000.000000',
            $this->withTenantExecutionContext(
                $tenantId,
                fn (): string => (string) $invoice->refresh()->balance_due,
            ),
        );
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-PAR-'.$suffix,
            'name' => 'Payment Allocation Reversal '.$suffix,
            'slug' => 'payment-allocation-reversal-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
