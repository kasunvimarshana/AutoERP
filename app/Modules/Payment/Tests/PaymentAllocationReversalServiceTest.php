<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Services\PaymentAllocationReversalService;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Tenant\Constants\TenantStatus;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class PaymentAllocationReversalServiceTest extends TestCase
{
    use RefreshDatabase;

    private const PAYMENT_DATE = '2026-07-11';
    private const PAYMENT_METHOD_CODE = 'TEST-CASH';
    private const PAYMENT_METHOD_NAME = 'Test Cash';
    private const ALLOCATION_METHOD = 'specific_invoice';

    public function test_it_reverses_one_invoice_allocation_and_allows_a_corrected_allocation_with_history(): void
    {
        $tenantId = $this->createTenant();
        FinancePostingFixture::seedCustomerPaymentProfiles($tenantId);
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-ALLOC-REVERSAL',
            'code' => 'CUS-ALLOC-REVERSAL',
            'name' => 'Allocation Reversal Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $postingPlan = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            self::PAYMENT_DATE,
            FinanceAccountRoleCode::Revenue,
            '1000.000000',
            description: 'Deposit allocation reversal invoice',
        );
        $invoice = $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $customerId, $postingPlan) {
            $invoice = app(InvoiceCreationService::class)->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Manual,
                direction: InvoiceDirection::Outbound,
                invoiceDate: self::PAYMENT_DATE,
                invoiceNumber: 'INV-ALLOC-REVERSAL',
                partyType: 'customer',
                partyId: $customerId,
                lines: [new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Deposit allocation reversal invoice',
                    quantity: '1.000000',
                    unitPrice: '1000.000000',
                )],
                postingPlan: $postingPlan,
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
            'code' => self::PAYMENT_METHOD_CODE,
            'name' => self::PAYMENT_METHOD_NAME,
            'method_type' => PaymentMethodType::Cash->value,
            'direction_allowed' => PaymentMethodDirection::Both->value,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'payment_number' => 'PAY-ALLOC-REVERSAL',
            'payment_type' => PaymentType::Advance->value,
            'direction' => PaymentDirection::Inbound->value,
            'party_type' => 'customer',
            'party_id' => $customerId,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'allocation_status' => PaymentAllocationState::FullyAllocated->value,
            'posting_status' => PaymentPostingStatus::Posted->value,
            'payment_date' => self::PAYMENT_DATE,
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
            'payment_method_code_snapshot' => self::PAYMENT_METHOD_CODE,
            'payment_method_name_snapshot' => self::PAYMENT_METHOD_NAME,
            'payment_method_type_snapshot' => PaymentMethodType::Cash->value,
            'amount' => '400.000000',
            'status' => PaymentInstrumentStatus::Cleared->value,
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
            'allocation_date' => self::PAYMENT_DATE,
            'allocation_method' => self::ALLOCATION_METHOD,
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
        $this->assertSame('1000.000000', $this->invoiceBalance($tenantId, $invoice));

        $corrected = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Payment => app(PaymentAllocationService::class)->allocate(
                $reversed,
                [new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: '250.000000',
                    allocationDate: self::PAYMENT_DATE,
                    allocationMethod: self::ALLOCATION_METHOD,
                )],
                (int) $reversed->row_version,
            ),
        );

        $this->assertSame('250.000000', (string) $corrected->allocated_amount);
        $this->assertSame('150.000000', (string) $corrected->unapplied_amount);
        $this->assertSame('750.000000', $this->invoiceBalance($tenantId, $invoice));
        $allocations = $this->withTenantExecutionContext(
            $tenantId,
            fn () => PaymentAllocation::query()
                ->where('payment_id', $paymentId)
                ->where('invoice_id', $invoice->getKey())
                ->orderBy('id')
                ->get(),
        );
        $this->assertCount(2, $allocations);
        $this->assertSame(AllocationStatus::Reversed, $allocations[0]->status);
        $this->assertNull($allocations[0]->active_identity_slot);
        $this->assertSame(AllocationStatus::Active, $allocations[1]->status);
        $this->assertSame(PaymentAllocation::ACTIVE_IDENTITY_SLOT, $allocations[1]->active_identity_slot);
    }

    private function invoiceBalance(int $tenantId, object $invoice): string
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): string => (string) $invoice->refresh()->balance_due,
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
            'status' => TenantStatus::ACTIVE,
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
