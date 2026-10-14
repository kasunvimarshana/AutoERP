<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Services\PaymentAllocationReversalService;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
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

        $payment = $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $customerId, $paymentMethodId): Payment {
            $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
                tenantId: $tenantId,
                paymentType: PaymentType::Advance,
                direction: PaymentDirection::Inbound,
                paymentDate: self::PAYMENT_DATE,
                partyType: 'customer',
                partyId: $customerId,
                lines: [new PaymentLineData(
                    amount: '400.000000',
                    paymentMethodId: $paymentMethodId,
                )],
            ));
            $lifecycle = app(PaymentDocumentLifecycleService::class);
            $payment = $lifecycle->submit($payment, (int) $payment->row_version);
            $payment = $lifecycle->approve($payment, (int) $payment->row_version);

            return app(PaymentPostingService::class)->post($payment, (int) $payment->row_version);
        });

        $payment = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Payment => app(PaymentAllocationService::class)->allocate(
                $payment,
                [new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: '400.000000',
                    allocationDate: self::PAYMENT_DATE,
                    allocationMethod: self::ALLOCATION_METHOD,
                )],
                (int) $payment->row_version,
            ),
        );
        $allocation = $this->withTenantExecutionContext(
            $tenantId,
            fn (): PaymentAllocation => PaymentAllocation::query()
                ->where('payment_id', $payment->getKey())
                ->where('invoice_id', $invoice->getKey())
                ->where('status', AllocationStatus::Active->value)
                ->sole(),
        );

        $reversed = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Payment => app(PaymentAllocationReversalService::class)->reverse(
                $payment,
                (int) $allocation->getKey(),
                (int) $payment->row_version,
                (int) $allocation->row_version,
                self::PAYMENT_DATE,
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
                ->where('payment_id', $payment->getKey())
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
