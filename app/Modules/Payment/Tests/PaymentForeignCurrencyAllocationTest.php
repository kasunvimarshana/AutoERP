<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Constants\FinanceSystemAccountCode;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
use Tests\Support\CurrencyFixture;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class PaymentForeignCurrencyAllocationTest extends TestCase
{
    use RefreshDatabase;

    private const INVOICE_DATE = '2026-10-01';

    private const PAYMENT_DATE = '2026-10-02';

    public function test_supplier_payment_allocation_recognizes_realized_fx_and_clears_payable(): void
    {
        $baseCurrencyId = CurrencyFixture::create(['name' => 'Base Currency']);
        $foreignCurrencyId = CurrencyFixture::create(['name' => 'Owner Settlement Currency']);
        $tenantId = $this->tenant($baseCurrencyId);
        FinancePostingFixture::seedSupplierPaymentProfiles($tenantId);
        $supplierId = $this->supplier($tenantId);
        $paymentMethodId = $this->paymentMethod($tenantId);

        $invoice = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => $this->postedSupplierInvoice(
                $tenantId,
                $supplierId,
                $foreignCurrencyId,
            ),
        );
        $payment = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Payment => $this->postedSupplierPayment(
                $tenantId,
                $supplierId,
                $foreignCurrencyId,
                $paymentMethodId,
            ),
        );

        $payment = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Payment => app(PaymentAllocationService::class)->allocate(
                $payment,
                [new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: '100.000000',
                    allocationDate: self::PAYMENT_DATE,
                    allocationMethod: 'specific_invoice',
                )],
                (int) $payment->row_version,
            ),
        );

        $allocation = $this->withTenantExecutionContext(
            $tenantId,
            fn (): PaymentAllocation => PaymentAllocation::query()
                ->where('payment_id', $payment->getKey())
                ->where('invoice_id', $invoice->getKey())
                ->sole(),
        );
        self::assertSame('2.000000', (string) $allocation->invoice_exchange_rate_snapshot);
        self::assertSame('0.000000', (string) $invoice->refresh()->balance_due);

        $fxJournalId = (int) DB::table('finance_journal_entries')
            ->where('tenant_id', $tenantId)
            ->where('source_type', PaymentSourceType::PaymentAllocationFx->value)
            ->where('source_id', $allocation->getKey())
            ->where('status', 'posted')
            ->value('id');
        self::assertGreaterThan(0, $fxJournalId);

        $lossAccountId = (int) DB::table('finance_accounts')
            ->where('tenant_id', $tenantId)
            ->where('code', FinanceSystemAccountCode::REALIZED_FX_LOSS)
            ->value('id');
        $this->assertDatabaseHas('finance_journal_lines', [
            'journal_entry_id' => $fxJournalId,
            'account_id' => $lossAccountId,
            'debit' => '100.000000',
            'credit' => '0.000000',
        ]);
        $this->assertDatabaseHas('finance_ledger_entries', [
            'journal_entry_id' => $fxJournalId,
            'account_id' => $lossAccountId,
            'base_debit' => '100.000000',
            'base_credit' => '0.000000',
        ]);
    }

    private function postedSupplierInvoice(
        int $tenantId,
        int $supplierId,
        int $currencyId,
    ): Invoice {
        $plan = app(InvoicePostingPlanFactory::class)->inbound(
            FinancePostingProfileCode::PurchaseInvoice,
            self::INVOICE_DATE,
            FinanceAccountRoleCode::Expense,
            '100.000000',
            description: 'Owner settlement FX invoice',
        );
        $invoice = app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Inbound,
            invoiceDate: self::INVOICE_DATE,
            invoiceNumber: 'OWN-FX-INV',
            partyType: 'supplier',
            partyId: $supplierId,
            currencyId: $currencyId,
            exchangeRate: '2.000000',
            lines: [new InvoiceLineData(
                lineNumber: 1,
                description: 'Owner settlement',
                quantity: '1.000000',
                unitPrice: '100.000000',
            )],
            postingPlan: $plan,
        ));
        $statuses = app(InvoiceStatusService::class);
        $invoice = $statuses->transition($invoice, InvoiceStatus::Approved);

        return $statuses->transition($invoice, InvoiceStatus::Posted);
    }

    private function postedSupplierPayment(
        int $tenantId,
        int $supplierId,
        int $currencyId,
        int $paymentMethodId,
    ): Payment {
        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::SupplierPayment,
            direction: PaymentDirection::Outbound,
            paymentDate: self::PAYMENT_DATE,
            partyType: 'supplier',
            partyId: $supplierId,
            currencyId: $currencyId,
            exchangeRate: '3.000000',
            lines: [new PaymentLineData(
                amount: '100.000000',
                paymentMethodId: $paymentMethodId,
            )],
        ));
        $lifecycle = app(PaymentDocumentLifecycleService::class);
        $payment = $lifecycle->submit($payment, (int) $payment->row_version);
        $payment = $lifecycle->approve($payment, (int) $payment->row_version);

        return app(PaymentPostingService::class)->post($payment, (int) $payment->row_version);
    }

    private function tenant(int $baseCurrencyId): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-OWN-FX-'.$suffix,
            'name' => 'Owner FX '.$suffix,
            'slug' => 'owner-fx-'.Str::lower($suffix),
            'base_currency_id' => $baseCurrencyId,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function supplier(int $tenantId): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_number' => 'SUP-'.$suffix,
            'code' => 'SUP-'.$suffix,
            'name' => 'Owner '.$suffix,
            'supplier_type' => 'individual',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentMethod(int $tenantId): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => 'OWNER-FX-CASH',
            'name' => 'Owner FX cash',
            'method_type' => PaymentMethodType::Cash->value,
            'direction_allowed' => PaymentMethodDirection::Both->value,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
