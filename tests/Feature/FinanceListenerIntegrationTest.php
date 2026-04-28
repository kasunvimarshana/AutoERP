<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Contracts\CreateApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\HR\Application\Contracts\ApprovePayrollRunServiceInterface;
use Modules\Inventory\Application\Contracts\CompleteCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\StartCycleCountServiceInterface;
use Modules\Purchase\Application\Contracts\ApprovePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\PostPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\RecordPurchasePaymentServiceInterface;
use Modules\Sales\Application\Contracts\RecordSalesPaymentServiceInterface;
use Modules\Sales\Application\Contracts\ReceiveSalesReturnServiceInterface;
use Modules\Finance\Infrastructure\Listeners\HandlePayrollRunApproved;
use Modules\Finance\Infrastructure\Listeners\HandlePurchaseInvoiceApproved;
use Modules\Finance\Infrastructure\Listeners\HandlePurchasePaymentRecorded;
use Modules\Finance\Infrastructure\Listeners\HandlePurchaseReturnPosted;
use Modules\Finance\Infrastructure\Listeners\HandleSalesPaymentRecorded;
use Modules\Finance\Infrastructure\Listeners\HandleSalesInvoicePosted;
use Modules\Finance\Infrastructure\Listeners\HandleSalesReturnReceived;
use Modules\Sales\Application\Contracts\PostSalesInvoiceServiceInterface;
use Modules\HR\Domain\Entities\PayrollRun;
use Modules\HR\Domain\Events\PayrollRunApproved;
use Modules\HR\Domain\ValueObjects\PayrollRunStatus;
use Modules\Purchase\Domain\Events\PurchaseInvoiceApproved;
use Modules\Purchase\Domain\Events\PurchasePaymentRecorded;
use Modules\Purchase\Domain\Events\PurchaseReturnPosted;
use Modules\Sales\Domain\Events\SalesInvoicePosted;
use Modules\Sales\Domain\Events\SalesPaymentRecorded;
use Modules\Sales\Domain\Events\SalesReturnReceived;
use Tests\TestCase;

class FinanceListenerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $apAccountId = 1;

    private int $inventoryAccountId = 2;

    private int $arAccountId = 3;

    private int $revenueAccountId = 4;

    private int $cashAccountId = 5;

    private int $payrollExpenseAccountId = 6;

    private int $payrollLiabilityAccountId = 7;

    private int $payrollDeductionsAccountId = 8;

    private int $customerId = 2;

    private int $uomId = 1;

    private int $productId = 1;

    private int $paymentMethodId = 1;

    private int $warehouseId = 1;

    private int $warehouseLocationId = 1;

    private int $fiscalYearId = 1;

    private int $fiscalPeriodId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    // ──────────────────────────────────────────────────────────────────
    // HandlePurchaseInvoiceApproved
    // ──────────────────────────────────────────────────────────────────

    public function test_handle_purchase_invoice_approved_creates_journal_entry(): void
    {
        $event = new PurchaseInvoiceApproved(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 99,
            supplierId: 1,
            apAccountId: $this->apAccountId,
            grandTotal: '110.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            invoiceDate: '2026-01-15',
            lines: [
                ['account_id' => $this->inventoryAccountId, 'line_total' => '100.000000', 'tax_amount' => '10.000000'],
            ],
        );

        $this->makeApListener()->handle($event);

        $this->assertSame(1, DB::table('journal_entries')->count());

        $je = DB::table('journal_entries')->first();
        $this->assertSame('system', $je->entry_type);
        $this->assertSame('purchase_invoice', $je->reference_type);
        $this->assertSame(99, (int) $je->reference_id);

        $lines = DB::table('journal_entry_lines')->where('journal_entry_id', $je->id)->orderBy('debit_amount', 'desc')->get();
        $this->assertCount(2, $lines);

        // Debit line: inventory account
        $debitLine = $lines[0];
        $this->assertSame($this->inventoryAccountId, (int) $debitLine->account_id);
        $this->assertEqualsWithDelta(110.0, (float) $debitLine->debit_amount, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $debitLine->credit_amount, 0.001);

        // Credit line: AP account
        $creditLine = $lines[1];
        $this->assertSame($this->apAccountId, (int) $creditLine->account_id);
        $this->assertEqualsWithDelta(0.0, (float) $creditLine->debit_amount, 0.001);
        $this->assertEqualsWithDelta(110.0, (float) $creditLine->credit_amount, 0.001);
    }

    public function test_handle_purchase_invoice_approved_aggregates_multiple_lines_by_account(): void
    {
        $event = new PurchaseInvoiceApproved(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 100,
            supplierId: 1,
            apAccountId: $this->apAccountId,
            grandTotal: '300.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            invoiceDate: '2026-01-15',
            lines: [
                ['account_id' => $this->inventoryAccountId, 'line_total' => '100.000000', 'tax_amount' => '0.000000'],
                ['account_id' => $this->inventoryAccountId, 'line_total' => '200.000000', 'tax_amount' => '0.000000'],
            ],
        );

        $this->makeApListener()->handle($event);

        $this->assertSame(1, DB::table('journal_entries')->count());
        $lines = DB::table('journal_entry_lines')->get();
        $this->assertCount(2, $lines); // Aggregated into 1 debit + 1 credit
    }

    public function test_handle_purchase_invoice_approved_skips_when_ap_account_is_null(): void
    {
        $event = new PurchaseInvoiceApproved(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 101,
            supplierId: 1,
            apAccountId: null,
            grandTotal: '100.000000',
            invoiceDate: '2026-01-15',
            lines: [
                ['account_id' => $this->inventoryAccountId, 'line_total' => '100.000000', 'tax_amount' => '0.000000'],
            ],
        );

        $this->makeApListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    public function test_handle_purchase_invoice_approved_skips_when_lines_are_empty(): void
    {
        $event = new PurchaseInvoiceApproved(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 102,
            supplierId: 1,
            apAccountId: $this->apAccountId,
            grandTotal: '100.000000',
            invoiceDate: '2026-01-15',
            lines: [],
        );

        $this->makeApListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    public function test_handle_purchase_invoice_approved_skips_when_line_missing_account_id(): void
    {
        $event = new PurchaseInvoiceApproved(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 103,
            supplierId: 1,
            apAccountId: $this->apAccountId,
            grandTotal: '100.000000',
            invoiceDate: '2026-01-15',
            lines: [
                ['account_id' => null, 'line_total' => '100.000000', 'tax_amount' => '0.000000'],
            ],
        );

        $this->makeApListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    public function test_handle_purchase_invoice_approved_skips_when_totals_do_not_balance(): void
    {
        $event = new PurchaseInvoiceApproved(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 104,
            supplierId: 1,
            apAccountId: $this->apAccountId,
            grandTotal: '150.000000',
            invoiceDate: '2026-01-15',
            lines: [
                ['account_id' => $this->inventoryAccountId, 'line_total' => '100.000000', 'tax_amount' => '0.000000'],
            ],
        );

        $this->makeApListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    public function test_handle_purchase_invoice_approved_skips_when_no_open_fiscal_period(): void
    {
        DB::table('fiscal_periods')->update(['status' => 'closed']);

        $event = new PurchaseInvoiceApproved(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 105,
            supplierId: 1,
            apAccountId: $this->apAccountId,
            grandTotal: '100.000000',
            invoiceDate: '2026-01-15',
            lines: [
                ['account_id' => $this->inventoryAccountId, 'line_total' => '100.000000', 'tax_amount' => '0.000000'],
            ],
        );

        $this->makeApListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    // ──────────────────────────────────────────────────────────────────
    // HandleSalesInvoicePosted
    // ──────────────────────────────────────────────────────────────────

    public function test_handle_sales_invoice_posted_creates_journal_entry(): void
    {
        $event = new SalesInvoicePosted(
            tenantId: $this->tenantId,
            salesInvoiceId: 200,
            customerId: 1,
            arAccountId: $this->arAccountId,
            grandTotal: '220.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            invoiceDate: '2026-01-20',
            lines: [
                ['income_account_id' => $this->revenueAccountId, 'line_total' => '200.000000', 'tax_amount' => '20.000000'],
            ],
        );

        $this->makeArListener()->handle($event);

        $this->assertSame(1, DB::table('journal_entries')->count());

        $je = DB::table('journal_entries')->first();
        $this->assertSame('system', $je->entry_type);
        $this->assertSame('sales_invoice', $je->reference_type);
        $this->assertSame(200, (int) $je->reference_id);

        $lines = DB::table('journal_entry_lines')->where('journal_entry_id', $je->id)->orderBy('debit_amount', 'desc')->get();
        $this->assertCount(2, $lines);

        // Debit line: AR account
        $debitLine = $lines[0];
        $this->assertSame($this->arAccountId, (int) $debitLine->account_id);
        $this->assertEqualsWithDelta(220.0, (float) $debitLine->debit_amount, 0.001);

        // Credit line: Revenue account
        $creditLine = $lines[1];
        $this->assertSame($this->revenueAccountId, (int) $creditLine->account_id);
        $this->assertEqualsWithDelta(220.0, (float) $creditLine->credit_amount, 0.001);
    }

    public function test_handle_sales_invoice_posted_skips_when_ar_account_is_null(): void
    {
        $event = new SalesInvoicePosted(
            tenantId: $this->tenantId,
            salesInvoiceId: 201,
            customerId: 1,
            arAccountId: null,
            grandTotal: '100.000000',
            invoiceDate: '2026-01-20',
            lines: [
                ['income_account_id' => $this->revenueAccountId, 'line_total' => '100.000000', 'tax_amount' => '0.000000'],
            ],
        );

        $this->makeArListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    public function test_handle_sales_invoice_posted_skips_when_line_missing_income_account_id(): void
    {
        $event = new SalesInvoicePosted(
            tenantId: $this->tenantId,
            salesInvoiceId: 202,
            customerId: 1,
            arAccountId: $this->arAccountId,
            grandTotal: '100.000000',
            invoiceDate: '2026-01-20',
            lines: [
                ['income_account_id' => null, 'line_total' => '100.000000', 'tax_amount' => '0.000000'],
            ],
        );

        $this->makeArListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    public function test_handle_sales_invoice_posted_skips_when_totals_do_not_balance(): void
    {
        $event = new SalesInvoicePosted(
            tenantId: $this->tenantId,
            salesInvoiceId: 203,
            customerId: 1,
            arAccountId: $this->arAccountId,
            grandTotal: '200.000000',
            invoiceDate: '2026-01-20',
            lines: [
                ['income_account_id' => $this->revenueAccountId, 'line_total' => '100.000000', 'tax_amount' => '0.000000'],
            ],
        );

        $this->makeArListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    // ──────────────────────────────────────────────────────────────────
    // HandlePurchasePaymentRecorded
    // ──────────────────────────────────────────────────────────────────

    public function test_handle_purchase_payment_recorded_creates_journal_entry_and_ap_transaction(): void
    {
        $event = new PurchasePaymentRecorded(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 50,
            supplierId: 1,
            paymentId: 10,
            apAccountId: $this->apAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '500.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-20',
        );

        $this->makePaymentListener()->handle($event);

        // One journal entry created
        $this->assertSame(1, DB::table('journal_entries')->count());

        $je = DB::table('journal_entries')->first();
        $this->assertSame('system', $je->entry_type);
        $this->assertSame('purchase_payment', $je->reference_type);
        $this->assertSame(10, (int) $je->reference_id);

        $lines = DB::table('journal_entry_lines')
            ->where('journal_entry_id', $je->id)
            ->orderBy('debit_amount', 'desc')
            ->get();

        $this->assertCount(2, $lines);

        // DR: AP account
        $debitLine = $lines[0];
        $this->assertSame($this->apAccountId, (int) $debitLine->account_id);
        $this->assertEqualsWithDelta(500.0, (float) $debitLine->debit_amount, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $debitLine->credit_amount, 0.001);

        // CR: Cash/Bank account
        $creditLine = $lines[1];
        $this->assertSame($this->cashAccountId, (int) $creditLine->account_id);
        $this->assertEqualsWithDelta(0.0, (float) $creditLine->debit_amount, 0.001);
        $this->assertEqualsWithDelta(500.0, (float) $creditLine->credit_amount, 0.001);

        // AP transaction recorded with type 'payment'
        $this->assertSame(1, DB::table('ap_transactions')->count());
        $ap = DB::table('ap_transactions')->first();
        $this->assertSame('payment', $ap->transaction_type);
        $this->assertSame('purchase_payment', $ap->reference_type);
        $this->assertSame(10, (int) $ap->reference_id);
        $this->assertEqualsWithDelta(-500.0, (float) $ap->amount, 0.001);
    }

    public function test_handle_purchase_payment_recorded_reduces_supplier_ap_balance(): void
    {
        // First seed an existing AP balance by inserting a bill transaction
        DB::table('ap_transactions')->insert([
            'tenant_id' => $this->tenantId,
            'supplier_id' => 1,
            'account_id' => $this->apAccountId,
            'transaction_type' => 'bill',
            'amount' => '1000.000000',
            'balance_after' => '1000.000000',
            'transaction_date' => '2026-01-10',
            'currency_id' => 1,
            'is_reconciled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = new PurchasePaymentRecorded(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 55,
            supplierId: 1,
            paymentId: 11,
            apAccountId: $this->apAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '400.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-20',
        );

        $this->makePaymentListener()->handle($event);

        $apTransaction = DB::table('ap_transactions')
            ->where('transaction_type', 'payment')
            ->first();

        $this->assertNotNull($apTransaction);
        // Balance after = 1000 - 400 = 600
        $this->assertEqualsWithDelta(600.0, (float) $apTransaction->balance_after, 0.001);
    }

    public function test_handle_purchase_payment_recorded_duplicate_event_is_replay_safe(): void
    {
        $event = new PurchasePaymentRecorded(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 59,
            supplierId: 1,
            paymentId: 15,
            apAccountId: $this->apAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '150.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-20',
        );

        $this->makePaymentListener()->handle($event);
        $this->makePaymentListener()->handle($event);

        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'purchase_payment')->where('reference_id', 15)->count());
        $this->assertSame(1, DB::table('ap_transactions')->where('reference_type', 'purchase_payment')->where('reference_id', 15)->count());
    }

    public function test_handle_sales_payment_recorded_duplicate_event_is_replay_safe(): void
    {
        $event = new SalesPaymentRecorded(
            tenantId: $this->tenantId,
            salesInvoiceId: 260,
            customerId: $this->customerId,
            paymentId: 16,
            arAccountId: $this->arAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '180.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-21',
        );

        $this->makeSalesPaymentListener()->handle($event);
        $this->makeSalesPaymentListener()->handle($event);

        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'sales_payment')->where('reference_id', 16)->count());
        $this->assertSame(1, DB::table('ar_transactions')->where('reference_type', 'sales_payment')->where('reference_id', 16)->count());
    }

    public function test_handle_purchase_payment_recorded_duplicate_conflict_with_partial_artifacts_throws(): void
    {
        DB::table('journal_entries')->insert([
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'fiscal_period_id' => $this->fiscalPeriodId,
            'entry_number' => null,
            'entry_type' => 'system',
            'reference_type' => 'purchase_payment',
            'reference_id' => 996,
            'description' => 'Pre-existing partial artifact',
            'entry_date' => '2026-01-20',
            'posting_date' => null,
            'status' => 'posted',
            'is_reversed' => false,
            'reversal_entry_id' => null,
            'created_by' => 1,
            'posted_by' => null,
            'posted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $event = new PurchasePaymentRecorded(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 559,
            supplierId: 1,
            paymentId: 996,
            apAccountId: $this->apAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '150.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-20',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('incomplete finance artifacts');

        $this->makePaymentListener()->handle($event);
    }

    public function test_handle_sales_payment_recorded_duplicate_conflict_with_partial_artifacts_throws(): void
    {
        DB::table('journal_entries')->insert([
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'fiscal_period_id' => $this->fiscalPeriodId,
            'entry_number' => null,
            'entry_type' => 'system',
            'reference_type' => 'sales_payment',
            'reference_id' => 997,
            'description' => 'Pre-existing partial artifact',
            'entry_date' => '2026-01-21',
            'posting_date' => null,
            'status' => 'posted',
            'is_reversed' => false,
            'reversal_entry_id' => null,
            'created_by' => 1,
            'posted_by' => null,
            'posted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $event = new SalesPaymentRecorded(
            tenantId: $this->tenantId,
            salesInvoiceId: 560,
            customerId: $this->customerId,
            paymentId: 997,
            arAccountId: $this->arAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '180.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-21',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('incomplete finance artifacts');

        $this->makeSalesPaymentListener()->handle($event);
    }

    public function test_handle_purchase_return_posted_duplicate_event_is_replay_safe(): void
    {
        $event = new PurchaseReturnPosted(
            tenantId: $this->tenantId,
            purchaseReturnId: 875,
            supplierId: 1,
            apAccountId: $this->apAccountId,
            grandTotal: '55.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            returnDate: '2026-01-24',
            lines: [
                [
                    'id' => null,
                    'product_id' => $this->productId,
                    'from_location_id' => $this->warehouseLocationId,
                    'uom_id' => $this->uomId,
                    'return_qty' => '1.000000',
                    'unit_cost' => '55.000000',
                    'variant_id' => null,
                    'batch_id' => null,
                    'serial_id' => null,
                    'account_id' => $this->inventoryAccountId,
                    'line_total' => '55.000000',
                    'tax_amount' => '0.000000',
                ],
            ],
        );

        $this->makePurchaseReturnListener()->handle($event);
        $this->makePurchaseReturnListener()->handle($event);

        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'purchase_return')->where('reference_id', 875)->count());
        $this->assertSame(1, DB::table('ap_transactions')->where('reference_type', 'purchase_return')->where('reference_id', 875)->count());
    }

    public function test_handle_sales_return_received_duplicate_event_is_replay_safe(): void
    {
        $event = new SalesReturnReceived(
            tenantId: $this->tenantId,
            salesReturnId: 895,
            customerId: $this->customerId,
            arAccountId: $this->arAccountId,
            grandTotal: '60.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            returnDate: '2026-01-25',
            lines: [
                [
                    'id' => null,
                    'product_id' => $this->productId,
                    'to_location_id' => $this->warehouseLocationId,
                    'uom_id' => $this->uomId,
                    'return_qty' => '1.000000',
                    'variant_id' => null,
                    'batch_id' => null,
                    'serial_id' => null,
                    'income_account_id' => $this->revenueAccountId,
                    'line_total' => '60.000000',
                ],
            ],
        );

        $this->makeSalesReturnListener()->handle($event);
        $this->makeSalesReturnListener()->handle($event);

        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'sales_return')->where('reference_id', 895)->count());
        $this->assertSame(1, DB::table('ar_transactions')->where('reference_type', 'sales_return')->where('reference_id', 895)->count());
    }

    public function test_handle_purchase_return_posted_duplicate_conflict_with_partial_artifacts_throws(): void
    {
        DB::table('journal_entries')->insert([
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'fiscal_period_id' => $this->fiscalPeriodId,
            'entry_number' => null,
            'entry_type' => 'system',
            'reference_type' => 'purchase_return',
            'reference_id' => 998,
            'description' => 'Pre-existing partial artifact',
            'entry_date' => '2026-01-24',
            'posting_date' => null,
            'status' => 'posted',
            'is_reversed' => false,
            'reversal_entry_id' => null,
            'created_by' => 1,
            'posted_by' => null,
            'posted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $event = new PurchaseReturnPosted(
            tenantId: $this->tenantId,
            purchaseReturnId: 998,
            supplierId: 1,
            apAccountId: $this->apAccountId,
            grandTotal: '55.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            returnDate: '2026-01-24',
            lines: [
                [
                    'id' => null,
                    'product_id' => $this->productId,
                    'from_location_id' => $this->warehouseLocationId,
                    'uom_id' => $this->uomId,
                    'return_qty' => '1.000000',
                    'unit_cost' => '55.000000',
                    'variant_id' => null,
                    'batch_id' => null,
                    'serial_id' => null,
                    'account_id' => $this->inventoryAccountId,
                    'line_total' => '55.000000',
                    'tax_amount' => '0.000000',
                ],
            ],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('incomplete finance artifacts');

        $this->makePurchaseReturnListener()->handle($event);
    }

    public function test_handle_sales_return_received_duplicate_conflict_with_partial_artifacts_throws(): void
    {
        DB::table('journal_entries')->insert([
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'fiscal_period_id' => $this->fiscalPeriodId,
            'entry_number' => null,
            'entry_type' => 'system',
            'reference_type' => 'sales_return',
            'reference_id' => 999,
            'description' => 'Pre-existing partial artifact',
            'entry_date' => '2026-01-25',
            'posting_date' => null,
            'status' => 'posted',
            'is_reversed' => false,
            'reversal_entry_id' => null,
            'created_by' => 1,
            'posted_by' => null,
            'posted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $event = new SalesReturnReceived(
            tenantId: $this->tenantId,
            salesReturnId: 999,
            customerId: $this->customerId,
            arAccountId: $this->arAccountId,
            grandTotal: '60.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            returnDate: '2026-01-25',
            lines: [
                [
                    'id' => null,
                    'product_id' => $this->productId,
                    'to_location_id' => $this->warehouseLocationId,
                    'uom_id' => $this->uomId,
                    'return_qty' => '1.000000',
                    'variant_id' => null,
                    'batch_id' => null,
                    'serial_id' => null,
                    'income_account_id' => $this->revenueAccountId,
                    'line_total' => '60.000000',
                ],
            ],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('incomplete finance artifacts');

        $this->makeSalesReturnListener()->handle($event);
    }

    public function test_handle_purchase_payment_recorded_transaction_first_partial_artifacts_throw(): void
    {
        DB::table('ap_transactions')->insert([
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'supplier_id' => 1,
            'account_id' => $this->apAccountId,
            'transaction_type' => 'payment',
            'reference_type' => 'purchase_payment',
            'reference_id' => 1001,
            'amount' => '-150.000000',
            'balance_after' => '600.000000',
            'transaction_date' => '2026-01-20',
            'due_date' => null,
            'currency_id' => 1,
            'is_reconciled' => false,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = new PurchasePaymentRecorded(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 1001,
            supplierId: 1,
            paymentId: 1001,
            apAccountId: $this->apAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '150.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-20',
        );

        try {
            $this->makePaymentListener()->handle($event);
            $this->fail('Expected incomplete-artifact replay conflict to throw RuntimeException.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('incomplete finance artifacts', strtolower($exception->getMessage()));
        }

        $this->assertSame(0, DB::table('journal_entries')->where('reference_type', 'purchase_payment')->where('reference_id', 1001)->count());
        $this->assertSame(1, DB::table('ap_transactions')->where('reference_type', 'purchase_payment')->where('reference_id', 1001)->count());
    }

    public function test_handle_sales_payment_recorded_transaction_first_partial_artifacts_throw(): void
    {
        DB::table('ar_transactions')->insert([
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $this->customerId,
            'account_id' => $this->arAccountId,
            'transaction_type' => 'payment',
            'reference_type' => 'sales_payment',
            'reference_id' => 1003,
            'amount' => '-180.000000',
            'balance_after' => '320.000000',
            'transaction_date' => '2026-01-21',
            'due_date' => null,
            'currency_id' => 1,
            'is_reconciled' => false,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = new SalesPaymentRecorded(
            tenantId: $this->tenantId,
            salesInvoiceId: 1003,
            customerId: $this->customerId,
            paymentId: 1003,
            arAccountId: $this->arAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '180.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-21',
        );

        try {
            $this->makeSalesPaymentListener()->handle($event);
            $this->fail('Expected incomplete-artifact replay conflict to throw RuntimeException.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('incomplete finance artifacts', strtolower($exception->getMessage()));
        }

        $this->assertSame(0, DB::table('journal_entries')->where('reference_type', 'sales_payment')->where('reference_id', 1003)->count());
        $this->assertSame(1, DB::table('ar_transactions')->where('reference_type', 'sales_payment')->where('reference_id', 1003)->count());
    }

    public function test_handle_purchase_return_posted_transaction_first_partial_artifacts_throw(): void
    {
        DB::table('ap_transactions')->insert([
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'supplier_id' => 1,
            'account_id' => $this->apAccountId,
            'transaction_type' => 'debit_note',
            'reference_type' => 'purchase_return',
            'reference_id' => 1004,
            'amount' => '-55.000000',
            'balance_after' => '545.000000',
            'transaction_date' => '2026-01-24',
            'due_date' => null,
            'currency_id' => 1,
            'is_reconciled' => false,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = new PurchaseReturnPosted(
            tenantId: $this->tenantId,
            purchaseReturnId: 1004,
            supplierId: 1,
            apAccountId: $this->apAccountId,
            grandTotal: '55.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            returnDate: '2026-01-24',
            lines: [
                [
                    'id' => null,
                    'product_id' => $this->productId,
                    'from_location_id' => $this->warehouseLocationId,
                    'uom_id' => $this->uomId,
                    'return_qty' => '1.000000',
                    'unit_cost' => '55.000000',
                    'variant_id' => null,
                    'batch_id' => null,
                    'serial_id' => null,
                    'account_id' => $this->inventoryAccountId,
                    'line_total' => '55.000000',
                    'tax_amount' => '0.000000',
                ],
            ],
        );

        try {
            $this->makePurchaseReturnListener()->handle($event);
            $this->fail('Expected incomplete-artifact replay conflict to throw RuntimeException.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('incomplete finance artifacts', strtolower($exception->getMessage()));
        }

        $this->assertSame(0, DB::table('journal_entries')->where('reference_type', 'purchase_return')->where('reference_id', 1004)->count());
        $this->assertSame(1, DB::table('ap_transactions')->where('reference_type', 'purchase_return')->where('reference_id', 1004)->count());
    }

    public function test_handle_sales_return_received_transaction_first_partial_artifacts_throw(): void
    {
        DB::table('ar_transactions')->insert([
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $this->customerId,
            'account_id' => $this->arAccountId,
            'transaction_type' => 'credit_memo',
            'reference_type' => 'sales_return',
            'reference_id' => 1002,
            'amount' => '-60.000000',
            'balance_after' => '240.000000',
            'transaction_date' => '2026-01-25',
            'due_date' => null,
            'currency_id' => 1,
            'is_reconciled' => false,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = new SalesReturnReceived(
            tenantId: $this->tenantId,
            salesReturnId: 1002,
            customerId: $this->customerId,
            arAccountId: $this->arAccountId,
            grandTotal: '60.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            returnDate: '2026-01-25',
            lines: [
                [
                    'id' => null,
                    'product_id' => $this->productId,
                    'to_location_id' => $this->warehouseLocationId,
                    'uom_id' => $this->uomId,
                    'return_qty' => '1.000000',
                    'variant_id' => null,
                    'batch_id' => null,
                    'serial_id' => null,
                    'income_account_id' => $this->revenueAccountId,
                    'line_total' => '60.000000',
                ],
            ],
        );

        try {
            $this->makeSalesReturnListener()->handle($event);
            $this->fail('Expected incomplete-artifact replay conflict to throw RuntimeException.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('incomplete finance artifacts', strtolower($exception->getMessage()));
        }

        $this->assertSame(0, DB::table('journal_entries')->where('reference_type', 'sales_return')->where('reference_id', 1002)->count());
        $this->assertSame(1, DB::table('ar_transactions')->where('reference_type', 'sales_return')->where('reference_id', 1002)->count());
    }

    public function test_handle_purchase_payment_recorded_skips_when_ap_account_is_null(): void
    {
        $event = new PurchasePaymentRecorded(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 56,
            supplierId: 1,
            paymentId: 12,
            apAccountId: null,
            cashAccountId: $this->cashAccountId,
            amount: '200.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-20',
        );

        $this->makePaymentListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('ap_transactions')->count());
    }

    public function test_handle_purchase_payment_recorded_skips_when_amount_is_zero(): void
    {
        $event = new PurchasePaymentRecorded(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 57,
            supplierId: 1,
            paymentId: 13,
            apAccountId: $this->apAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '0.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-20',
        );

        $this->makePaymentListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('ap_transactions')->count());
    }

    public function test_handle_purchase_payment_recorded_skips_when_no_open_fiscal_period(): void
    {
        DB::table('fiscal_periods')->update(['status' => 'closed']);

        $event = new PurchasePaymentRecorded(
            tenantId: $this->tenantId,
            purchaseInvoiceId: 58,
            supplierId: 1,
            paymentId: 14,
            apAccountId: $this->apAccountId,
            cashAccountId: $this->cashAccountId,
            amount: '300.000000',
            currencyId: 1,
            exchangeRate: '1.000000',
            paymentDate: '2026-01-20',
        );

        $this->makePaymentListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('ap_transactions')->count());
    }

    public function test_approve_purchase_invoice_service_dispatches_finance_posting_end_to_end(): void
    {
        DB::table('purchase_invoices')->insert([
            'id' => 701,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'supplier_id' => 1,
            'grn_header_id' => null,
            'purchase_order_id' => null,
            'invoice_number' => 'PINV-701',
            'supplier_invoice_number' => 'S-701',
            'status' => 'draft',
            'invoice_date' => '2026-01-15',
            'due_date' => '2026-01-30',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '100.000000',
            'tax_total' => '10.000000',
            'discount_total' => '0.000000',
            'grand_total' => '110.000000',
            'paid_amount' => '0.000000',
            'ap_account_id' => $this->apAccountId,
            'journal_entry_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('purchase_invoice_lines')->insert([
            'id' => 702,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'purchase_invoice_id' => 701,
            'grn_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'description' => 'Inbound inventory',
            'uom_id' => $this->uomId,
            'quantity' => '1.000000',
            'unit_price' => '100.000000',
            'discount_pct' => '0.000000',
            'tax_group_id' => null,
            'tax_amount' => '10.000000',
            'line_total' => '100.000000',
            'account_id' => $this->inventoryAccountId,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $result = app(ApprovePurchaseInvoiceServiceInterface::class)->execute([
            'id' => 701,
            'approved_by' => 1,
        ]);

        $this->assertSame(701, $result->getId());
        $this->assertSame('approved', $result->getStatus());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'purchase_invoice')->where('reference_id', 701)->count());
    }

    public function test_post_sales_invoice_service_dispatches_finance_posting_end_to_end(): void
    {
        DB::table('sales_invoices')->insert([
            'id' => 801,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $this->customerId,
            'sales_order_id' => null,
            'shipment_id' => null,
            'invoice_number' => 'SINV-801',
            'status' => 'draft',
            'invoice_date' => '2026-01-20',
            'due_date' => '2026-02-04',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '200.000000',
            'tax_total' => '20.000000',
            'discount_total' => '0.000000',
            'grand_total' => '220.000000',
            'paid_amount' => '0.000000',
            'ar_account_id' => $this->arAccountId,
            'journal_entry_id' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('sales_invoice_lines')->insert([
            'id' => 802,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'sales_invoice_id' => 801,
            'sales_order_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'description' => 'Outbound inventory',
            'uom_id' => $this->uomId,
            'quantity' => '1.000000',
            'unit_price' => '200.000000',
            'discount_pct' => '0.000000',
            'tax_group_id' => null,
            'tax_amount' => '20.000000',
            'line_total' => '200.000000',
            'income_account_id' => $this->revenueAccountId,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $result = app(PostSalesInvoiceServiceInterface::class)->execute([
            'id' => 801,
            'posted_by' => 1,
        ]);

        $this->assertSame(801, $result->getId());
        $this->assertSame('sent', $result->getStatus());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'sales_invoice')->where('reference_id', 801)->count());
    }

    public function test_record_purchase_payment_service_dispatches_finance_posting_end_to_end(): void
    {
        DB::table('purchase_invoices')->insert([
            'id' => 851,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'supplier_id' => 1,
            'grn_header_id' => null,
            'purchase_order_id' => null,
            'invoice_number' => 'PINV-851',
            'supplier_invoice_number' => 'S-851',
            'status' => 'approved',
            'invoice_date' => '2026-01-20',
            'due_date' => '2026-02-04',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '500.000000',
            'tax_total' => '0.000000',
            'discount_total' => '0.000000',
            'grand_total' => '500.000000',
            'paid_amount' => '0.000000',
            'ap_account_id' => $this->apAccountId,
            'journal_entry_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $result = app(RecordPurchasePaymentServiceInterface::class)->execute([
            'tenant_id' => $this->tenantId,
            'invoice_id' => 851,
            'payment_number' => 'PAY-851',
            'payment_method_id' => $this->paymentMethodId,
            'account_id' => $this->cashAccountId,
            'amount' => '200.000000',
            'currency_id' => 1,
            'payment_date' => '2026-01-21',
            'exchange_rate' => 1.0,
            'reference' => 'PUR-PMT-851',
            'notes' => 'Service-driven purchase payment',
        ]);

        $this->assertSame(851, $result->getId());
        $this->assertSame('partial_paid', $result->getStatus());
        $this->assertSame('200.000000', $result->getPaidAmount());

        $payment = DB::table('payments')->where('payment_number', 'PAY-851')->first();
        $this->assertNotNull($payment);
        $this->assertSame($this->tenantId, (int) $payment->tenant_id);

        $this->assertSame(1, DB::table('payment_allocations')->where('payment_id', $payment->id)->count());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'purchase_payment')->where('reference_id', $payment->id)->count());
        $this->assertSame(1, DB::table('ap_transactions')->where('reference_type', 'purchase_payment')->where('reference_id', $payment->id)->count());
    }

    public function test_record_sales_payment_service_dispatches_finance_posting_end_to_end(): void
    {
        DB::table('sales_invoices')->insert([
            'id' => 861,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $this->customerId,
            'sales_order_id' => null,
            'shipment_id' => null,
            'invoice_number' => 'SINV-861',
            'status' => 'sent',
            'invoice_date' => '2026-01-22',
            'due_date' => '2026-02-06',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '300.000000',
            'tax_total' => '0.000000',
            'discount_total' => '0.000000',
            'grand_total' => '300.000000',
            'paid_amount' => '0.000000',
            'ar_account_id' => $this->arAccountId,
            'journal_entry_id' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('sales_invoice_lines')->insert([
            'id' => 862,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'sales_invoice_id' => 861,
            'sales_order_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'description' => 'Customer payment invoice line',
            'uom_id' => $this->uomId,
            'quantity' => '1.000000',
            'unit_price' => '300.000000',
            'discount_pct' => '0.000000',
            'tax_group_id' => null,
            'tax_amount' => '0.000000',
            'line_total' => '300.000000',
            'income_account_id' => $this->revenueAccountId,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $result = app(RecordSalesPaymentServiceInterface::class)->execute([
            'tenant_id' => $this->tenantId,
            'invoice_id' => 861,
            'payment_number' => 'PAY-861',
            'payment_method_id' => $this->paymentMethodId,
            'account_id' => $this->cashAccountId,
            'amount' => '120.000000',
            'currency_id' => 1,
            'payment_date' => '2026-01-23',
            'exchange_rate' => 1.0,
            'reference' => 'SAL-PMT-861',
            'notes' => 'Service-driven sales payment',
        ]);

        $this->assertSame(861, $result->getId());
        $this->assertSame('partial_paid', $result->getStatus());
        $this->assertSame('120.000000', $result->getPaidAmount());

        $payment = DB::table('payments')->where('payment_number', 'PAY-861')->first();
        $this->assertNotNull($payment);
        $this->assertSame($this->tenantId, (int) $payment->tenant_id);

        $this->assertSame(1, DB::table('payment_allocations')->where('payment_id', $payment->id)->count());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'sales_payment')->where('reference_id', $payment->id)->count());
        $this->assertSame(1, DB::table('ar_transactions')->where('reference_type', 'sales_payment')->where('reference_id', $payment->id)->count());
    }

    public function test_record_purchase_payment_service_duplicate_submit_with_idempotency_key_is_replay_safe(): void
    {
        DB::table('purchase_invoices')->insert([
            'id' => 885,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'supplier_id' => 1,
            'grn_header_id' => null,
            'purchase_order_id' => null,
            'invoice_number' => 'PINV-885',
            'supplier_invoice_number' => 'S-885',
            'status' => 'approved',
            'invoice_date' => '2026-01-20',
            'due_date' => '2026-02-04',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '500.000000',
            'tax_total' => '0.000000',
            'discount_total' => '0.000000',
            'grand_total' => '500.000000',
            'paid_amount' => '0.000000',
            'ap_account_id' => $this->apAccountId,
            'journal_entry_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $payload = [
            'tenant_id' => $this->tenantId,
            'invoice_id' => 885,
            'payment_number' => 'PAY-885-A',
            'idempotency_key' => 'dup-purchase-885',
            'payment_method_id' => $this->paymentMethodId,
            'account_id' => $this->cashAccountId,
            'amount' => '200.000000',
            'currency_id' => 1,
            'payment_date' => '2026-01-21',
            'exchange_rate' => 1.0,
            'reference' => 'PUR-PMT-885',
            'notes' => 'Replay-safe purchase payment',
        ];

        $first = app(RecordPurchasePaymentServiceInterface::class)->execute($payload);
        $second = app(RecordPurchasePaymentServiceInterface::class)->execute(array_merge($payload, [
            'payment_number' => 'PAY-885-B',
        ]));

        $this->assertSame('200.000000', $first->getPaidAmount());
        $this->assertSame('200.000000', $second->getPaidAmount());

        $payment = DB::table('payments')->where('idempotency_key', 'dup-purchase-885')->first();
        $this->assertNotNull($payment);
        $this->assertSame(1, DB::table('payments')->where('idempotency_key', 'dup-purchase-885')->count());
        $this->assertSame(1, DB::table('payment_allocations')->where('payment_id', $payment->id)->count());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'purchase_payment')->where('reference_id', $payment->id)->count());
        $this->assertSame(1, DB::table('ap_transactions')->where('reference_type', 'purchase_payment')->where('reference_id', $payment->id)->count());
    }

    public function test_record_sales_payment_service_duplicate_submit_with_idempotency_key_is_replay_safe(): void
    {
        DB::table('sales_invoices')->insert([
            'id' => 886,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $this->customerId,
            'sales_order_id' => null,
            'shipment_id' => null,
            'invoice_number' => 'SINV-886',
            'status' => 'sent',
            'invoice_date' => '2026-01-22',
            'due_date' => '2026-02-06',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '300.000000',
            'tax_total' => '0.000000',
            'discount_total' => '0.000000',
            'grand_total' => '300.000000',
            'paid_amount' => '0.000000',
            'ar_account_id' => $this->arAccountId,
            'journal_entry_id' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('sales_invoice_lines')->insert([
            'id' => 887,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'sales_invoice_id' => 886,
            'sales_order_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'description' => 'Duplicate-safe customer payment line',
            'uom_id' => $this->uomId,
            'quantity' => '1.000000',
            'unit_price' => '300.000000',
            'discount_pct' => '0.000000',
            'tax_group_id' => null,
            'tax_amount' => '0.000000',
            'line_total' => '300.000000',
            'income_account_id' => $this->revenueAccountId,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $payload = [
            'tenant_id' => $this->tenantId,
            'invoice_id' => 886,
            'payment_number' => 'PAY-886-A',
            'idempotency_key' => 'dup-sales-886',
            'payment_method_id' => $this->paymentMethodId,
            'account_id' => $this->cashAccountId,
            'amount' => '120.000000',
            'currency_id' => 1,
            'payment_date' => '2026-01-23',
            'exchange_rate' => 1.0,
            'reference' => 'SAL-PMT-886',
            'notes' => 'Replay-safe sales payment',
        ];

        $first = app(RecordSalesPaymentServiceInterface::class)->execute($payload);
        $second = app(RecordSalesPaymentServiceInterface::class)->execute(array_merge($payload, [
            'payment_number' => 'PAY-886-B',
        ]));

        $this->assertSame('120.000000', $first->getPaidAmount());
        $this->assertSame('120.000000', $second->getPaidAmount());

        $payment = DB::table('payments')->where('idempotency_key', 'dup-sales-886')->first();
        $this->assertNotNull($payment);
        $this->assertSame(1, DB::table('payments')->where('idempotency_key', 'dup-sales-886')->count());
        $this->assertSame(1, DB::table('payment_allocations')->where('payment_id', $payment->id)->count());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'sales_payment')->where('reference_id', $payment->id)->count());
        $this->assertSame(1, DB::table('ar_transactions')->where('reference_type', 'sales_payment')->where('reference_id', $payment->id)->count());
    }

    public function test_post_purchase_return_service_dispatches_finance_posting_end_to_end(): void
    {
        DB::table('purchase_invoices')->insert([
            'id' => 871,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'supplier_id' => 1,
            'grn_header_id' => null,
            'purchase_order_id' => null,
            'invoice_number' => 'PINV-871',
            'supplier_invoice_number' => 'SUP-871',
            'status' => 'approved',
            'invoice_date' => '2026-01-22',
            'due_date' => '2026-02-06',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '55.000000',
            'tax_total' => '0.000000',
            'discount_total' => '0.000000',
            'grand_total' => '55.000000',
            'paid_amount' => '0.000000',
            'ap_account_id' => $this->apAccountId,
            'journal_entry_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('purchase_invoice_lines')->insert([
            'id' => 872,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'purchase_invoice_id' => 871,
            'grn_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'description' => 'Returnable inventory',
            'uom_id' => $this->uomId,
            'quantity' => '1.000000',
            'unit_price' => '55.000000',
            'discount_pct' => '0.000000',
            'tax_group_id' => null,
            'tax_amount' => '0.000000',
            'line_total' => '55.000000',
            'account_id' => $this->inventoryAccountId,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('purchase_returns')->insert([
            'id' => 873,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'supplier_id' => 1,
            'original_grn_id' => null,
            'original_invoice_id' => 871,
            'return_number' => 'PRTN-873',
            'status' => 'draft',
            'return_date' => '2026-01-24',
            'return_reason' => 'Defect return',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '55.000000',
            'tax_total' => '0.000000',
            'grand_total' => '55.000000',
            'debit_note_number' => null,
            'journal_entry_id' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('purchase_return_lines')->insert([
            'id' => 874,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'purchase_return_id' => 873,
            'original_grn_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'from_location_id' => $this->warehouseLocationId,
            'uom_id' => $this->uomId,
            'return_qty' => '1.000000',
            'unit_cost' => '55.000000',
            'condition' => 'good',
            'disposition' => 'return_to_vendor',
            'restocking_fee' => '0.000000',
            'quality_check_notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $result = app(PostPurchaseReturnServiceInterface::class)->execute([
            'id' => 873,
            'created_by' => 1,
        ]);

        $this->assertSame(873, $result->getId());
        $this->assertSame('approved', $result->getStatus());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'purchase_return')->where('reference_id', 873)->count());
        $this->assertSame(1, DB::table('ap_transactions')->where('reference_type', 'purchase_return')->where('reference_id', 873)->count());
    }

    public function test_receive_sales_return_service_dispatches_finance_posting_end_to_end(): void
    {
        DB::table('sales_invoices')->insert([
            'id' => 881,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $this->customerId,
            'sales_order_id' => null,
            'shipment_id' => null,
            'invoice_number' => 'SINV-881',
            'status' => 'sent',
            'invoice_date' => '2026-01-22',
            'due_date' => '2026-02-06',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '60.000000',
            'tax_total' => '0.000000',
            'discount_total' => '0.000000',
            'grand_total' => '60.000000',
            'paid_amount' => '0.000000',
            'ar_account_id' => $this->arAccountId,
            'journal_entry_id' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('sales_returns')->insert([
            'id' => 883,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $this->customerId,
            'original_sales_order_id' => null,
            'original_invoice_id' => 881,
            'return_number' => 'SRTN-883',
            'status' => 'approved',
            'return_date' => '2026-01-25',
            'return_reason' => 'Customer return',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '60.000000',
            'tax_total' => '0.000000',
            'restocking_fee_total' => '0.000000',
            'grand_total' => '60.000000',
            'credit_memo_number' => null,
            'journal_entry_id' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('sales_return_lines')->insert([
            'id' => 884,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'sales_return_id' => 883,
            'original_sales_order_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'to_location_id' => $this->warehouseLocationId,
            'uom_id' => $this->uomId,
            'return_qty' => '1.000000',
            'unit_price' => '60.000000',
            'condition' => 'good',
            'disposition' => 'restock',
            'restocking_fee' => '0.000000',
            'quality_check_notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $result = app(ReceiveSalesReturnServiceInterface::class)->execute([
            'id' => 883,
        ]);

        $this->assertSame(883, $result->getId());
        $this->assertSame('received', $result->getStatus());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'sales_return')->where('reference_id', 883)->count());
        $this->assertSame(1, DB::table('ar_transactions')->where('reference_type', 'sales_return')->where('reference_id', 883)->count());
    }

    public function test_post_purchase_return_service_duplicate_submit_does_not_duplicate_finance_artifacts(): void
    {
        DB::table('purchase_invoices')->insert([
            'id' => 888,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'supplier_id' => 1,
            'grn_header_id' => null,
            'purchase_order_id' => null,
            'invoice_number' => 'PINV-888',
            'supplier_invoice_number' => 'S-888',
            'status' => 'approved',
            'invoice_date' => '2026-01-20',
            'due_date' => '2026-02-04',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '55.000000',
            'tax_total' => '0.000000',
            'discount_total' => '0.000000',
            'grand_total' => '55.000000',
            'paid_amount' => '0.000000',
            'ap_account_id' => $this->apAccountId,
            'journal_entry_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('purchase_invoice_lines')->insert([
            'id' => 889,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'purchase_invoice_id' => 888,
            'grn_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'description' => 'Duplicate-safe purchase return line',
            'uom_id' => $this->uomId,
            'quantity' => '1.000000',
            'unit_price' => '55.000000',
            'discount_pct' => '0.000000',
            'tax_group_id' => null,
            'tax_amount' => '0.000000',
            'line_total' => '55.000000',
            'account_id' => $this->inventoryAccountId,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('purchase_returns')->insert([
            'id' => 890,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'supplier_id' => 1,
            'original_grn_id' => null,
            'original_invoice_id' => 888,
            'return_number' => 'PRTN-890',
            'status' => 'draft',
            'return_date' => '2026-01-24',
            'return_reason' => 'Duplicate submit guard',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '55.000000',
            'tax_total' => '0.000000',
            'grand_total' => '55.000000',
            'debit_note_number' => null,
            'journal_entry_id' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('purchase_return_lines')->insert([
            'id' => 891,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'purchase_return_id' => 890,
            'original_grn_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'from_location_id' => $this->warehouseLocationId,
            'uom_id' => $this->uomId,
            'return_qty' => '1.000000',
            'unit_cost' => '55.000000',
            'condition' => 'good',
            'disposition' => 'return_to_vendor',
            'restocking_fee' => '0.000000',
            'quality_check_notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        app(PostPurchaseReturnServiceInterface::class)->execute([
            'id' => 890,
            'created_by' => 1,
        ]);

        try {
            app(PostPurchaseReturnServiceInterface::class)->execute([
                'id' => 890,
                'created_by' => 1,
            ]);
            $this->fail('Duplicate purchase return post should be rejected by status guard.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('cannot be posted in its current state', strtolower($exception->getMessage()));
        }

        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'purchase_return')->where('reference_id', 890)->count());
        $this->assertSame(1, DB::table('ap_transactions')->where('reference_type', 'purchase_return')->where('reference_id', 890)->count());
    }

    public function test_receive_sales_return_service_duplicate_submit_does_not_duplicate_finance_artifacts(): void
    {
        DB::table('sales_invoices')->insert([
            'id' => 892,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $this->customerId,
            'sales_order_id' => null,
            'shipment_id' => null,
            'invoice_number' => 'SINV-892',
            'status' => 'sent',
            'invoice_date' => '2026-01-22',
            'due_date' => '2026-02-06',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '60.000000',
            'tax_total' => '0.000000',
            'discount_total' => '0.000000',
            'grand_total' => '60.000000',
            'paid_amount' => '0.000000',
            'ar_account_id' => $this->arAccountId,
            'journal_entry_id' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('sales_returns')->insert([
            'id' => 893,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $this->customerId,
            'original_sales_order_id' => null,
            'original_invoice_id' => 892,
            'return_number' => 'SRTN-893',
            'status' => 'approved',
            'return_date' => '2026-01-25',
            'return_reason' => 'Duplicate submit guard',
            'currency_id' => 1,
            'exchange_rate' => '1.000000',
            'subtotal' => '60.000000',
            'tax_total' => '0.000000',
            'restocking_fee_total' => '0.000000',
            'grand_total' => '60.000000',
            'credit_memo_number' => null,
            'journal_entry_id' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('sales_return_lines')->insert([
            'id' => 894,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'sales_return_id' => 893,
            'original_sales_order_line_id' => null,
            'product_id' => $this->productId,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'to_location_id' => $this->warehouseLocationId,
            'uom_id' => $this->uomId,
            'return_qty' => '1.000000',
            'unit_price' => '60.000000',
            'condition' => 'good',
            'disposition' => 'restock',
            'restocking_fee' => '0.000000',
            'quality_check_notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        app(ReceiveSalesReturnServiceInterface::class)->execute(['id' => 893]);

        try {
            app(ReceiveSalesReturnServiceInterface::class)->execute(['id' => 893]);
            $this->fail('Duplicate sales return receive should be rejected by status guard.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('only approved returns can be received', strtolower($exception->getMessage()));
        }

        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'sales_return')->where('reference_id', 893)->count());
        $this->assertSame(1, DB::table('ar_transactions')->where('reference_type', 'sales_return')->where('reference_id', 893)->count());
    }

    public function test_complete_cycle_count_service_dispatches_finance_posting_when_accounts_are_mapped(): void
    {
        DB::table('fiscal_periods')
            ->where('id', $this->fiscalPeriodId)
            ->update([
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ]);

        DB::table('products')
            ->where('id', $this->productId)
            ->update([
                'inventory_account_id' => $this->inventoryAccountId,
                'expense_account_id' => $this->payrollExpenseAccountId,
            ]);

        DB::table('stock_levels')->insert([
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'variant_id' => null,
            'location_id' => $this->warehouseLocationId,
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => $this->uomId,
            'quantity_on_hand' => '10.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '12.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $count = app(CreateCycleCountServiceInterface::class)->execute([
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'location_id' => $this->warehouseLocationId,
            'counted_by_user_id' => 1,
            'lines' => [[
                'product_id' => $this->productId,
                'uom_id' => $this->uomId,
                'unit_cost' => '12.000000',
            ]],
        ]);

        $inProgress = app(StartCycleCountServiceInterface::class)->execute($this->tenantId, (int) $count->getId());

        $completed = app(CompleteCycleCountServiceInterface::class)->execute(
            $this->tenantId,
            (int) $inProgress->getId(),
            1,
            [[
                'line_id' => (int) $inProgress->getLines()[0]->getId(),
                'counted_qty' => '13.000000',
            ]],
        );

        $this->assertSame('completed', $completed->getStatus());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'cycle_count')->where('reference_id', (int) $count->getId())->count());

        $journalEntry = DB::table('journal_entries')
            ->where('reference_type', 'cycle_count')
            ->where('reference_id', (int) $count->getId())
            ->first();

        $this->assertNotNull($journalEntry);

        $lines = DB::table('journal_entry_lines')
            ->where('journal_entry_id', $journalEntry->id)
            ->get()
            ->keyBy('account_id');

        $this->assertCount(2, $lines);
        $this->assertNotNull($lines->get($this->inventoryAccountId));
        $this->assertNotNull($lines->get($this->payrollExpenseAccountId));
    }

    public function test_record_stock_adjustment_service_dispatches_finance_posting_when_accounts_are_mapped(): void
    {
        DB::table('products')
            ->where('id', $this->productId)
            ->update([
                'inventory_account_id' => $this->inventoryAccountId,
                'expense_account_id' => $this->payrollExpenseAccountId,
            ]);

        DB::table('stock_levels')->insert([
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'variant_id' => null,
            'location_id' => $this->warehouseLocationId,
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => $this->uomId,
            'quantity_on_hand' => '10.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '12.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $movement = app(RecordStockMovementServiceInterface::class)->execute([
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'from_location_id' => $this->warehouseLocationId,
            'to_location_id' => null,
            'movement_type' => 'adjustment_out',
            'uom_id' => $this->uomId,
            'quantity' => '2.000000',
            'unit_cost' => '12.000000',
            'performed_by' => 1,
            'performed_at' => '2026-01-26',
            'notes' => 'Manual shrinkage adjustment',
        ]);

        $this->assertNotNull($movement->getId());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'stock_movement')->where('reference_id', (int) $movement->getId())->count());

        $journalEntry = DB::table('journal_entries')
            ->where('reference_type', 'stock_movement')
            ->where('reference_id', (int) $movement->getId())
            ->first();

        $this->assertNotNull($journalEntry);

        $lines = DB::table('journal_entry_lines')
            ->where('journal_entry_id', $journalEntry->id)
            ->get()
            ->keyBy('account_id');

        $this->assertCount(2, $lines);
        $this->assertNotNull($lines->get($this->inventoryAccountId));
        $this->assertNotNull($lines->get($this->payrollExpenseAccountId));
    }

    // ──────────────────────────────────────────────────────────────────
    // HandlePayrollRunApproved
    // ──────────────────────────────────────────────────────────────────

    public function test_handle_payroll_run_approved_creates_journal_entry(): void
    {
        $event = new PayrollRunApproved(
            payrollRun: $this->makePayrollRun(),
            tenantId: $this->tenantId,
        );

        $this->makePayrollListener()->handle($event);

        $this->assertSame(1, DB::table('journal_entries')->count());

        $journalEntry = DB::table('journal_entries')->first();
        $this->assertSame('system', $journalEntry->entry_type);
        $this->assertSame('payroll_run', $journalEntry->reference_type);
        $this->assertSame(501, (int) $journalEntry->reference_id);

        $lines = DB::table('journal_entry_lines')
            ->where('journal_entry_id', $journalEntry->id)
            ->orderBy('account_id')
            ->get()
            ->keyBy('account_id');

        $this->assertCount(3, $lines);

        $expenseLine = $lines->get($this->payrollExpenseAccountId);
        $this->assertNotNull($expenseLine);
        $this->assertEqualsWithDelta(1000.0, (float) $expenseLine->debit_amount, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $expenseLine->credit_amount, 0.001);

        $liabilityLine = $lines->get($this->payrollLiabilityAccountId);
        $this->assertNotNull($liabilityLine);
        $this->assertEqualsWithDelta(0.0, (float) $liabilityLine->debit_amount, 0.001);
        $this->assertEqualsWithDelta(800.0, (float) $liabilityLine->credit_amount, 0.001);

        $deductionsLine = $lines->get($this->payrollDeductionsAccountId);
        $this->assertNotNull($deductionsLine);
        $this->assertEqualsWithDelta(0.0, (float) $deductionsLine->debit_amount, 0.001);
        $this->assertEqualsWithDelta(200.0, (float) $deductionsLine->credit_amount, 0.001);
    }

    public function test_handle_payroll_run_approved_skips_when_accounts_missing_from_metadata(): void
    {
        $event = new PayrollRunApproved(
            payrollRun: $this->makePayrollRun(metadata: ['currency_id' => 1, 'exchange_rate' => 1]),
            tenantId: $this->tenantId,
        );

        $this->makePayrollListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('journal_entry_lines')->count());
    }

    public function test_handle_payroll_run_approved_skips_when_totals_do_not_balance(): void
    {
        $event = new PayrollRunApproved(
            payrollRun: $this->makePayrollRun(totalGross: '1000.000000', totalNet: '900.000000', totalDeductions: '50.000000'),
            tenantId: $this->tenantId,
        );

        $this->makePayrollListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('journal_entry_lines')->count());
    }

    public function test_handle_payroll_run_approved_skips_when_no_open_fiscal_period(): void
    {
        DB::table('fiscal_periods')->update(['status' => 'closed']);

        $event = new PayrollRunApproved(
            payrollRun: $this->makePayrollRun(),
            tenantId: $this->tenantId,
        );

        $this->makePayrollListener()->handle($event);

        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('journal_entry_lines')->count());
    }

    public function test_approve_payroll_run_service_dispatches_finance_posting_end_to_end(): void
    {
        DB::table('hr_payroll_runs')->insert([
            'id' => 901,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'processing',
            'processed_at' => '2026-01-31 09:00:00',
            'approved_at' => null,
            'approved_by' => null,
            'total_gross' => '1000.000000',
            'total_deductions' => '200.000000',
            'total_net' => '800.000000',
            'metadata' => json_encode([
                'payroll_expense_account_id' => $this->payrollExpenseAccountId,
                'payroll_liability_account_id' => $this->payrollLiabilityAccountId,
                'payroll_deductions_account_id' => $this->payrollDeductionsAccountId,
                'currency_id' => 1,
                'exchange_rate' => 1,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $result = app(ApprovePayrollRunServiceInterface::class)->execute([
            'id' => 901,
            'approved_by' => 1,
        ]);

        $this->assertSame(901, $result->getId());
        $this->assertSame(PayrollRunStatus::APPROVED, $result->getStatus());
        $this->assertSame(1, $result->getApprovedBy());
        $this->assertSame(1, DB::table('journal_entries')->where('reference_type', 'payroll_run')->where('reference_id', 901)->count());

        $journalEntry = DB::table('journal_entries')
            ->where('reference_type', 'payroll_run')
            ->where('reference_id', 901)
            ->first();

        $this->assertNotNull($journalEntry);
        $this->assertSame($this->tenantId, (int) $journalEntry->tenant_id);

        $lines = DB::table('journal_entry_lines')
            ->where('journal_entry_id', $journalEntry->id)
            ->get();

        $this->assertCount(3, $lines);
    }

    private function makePaymentListener(): HandlePurchasePaymentRecorded
    {
        return new HandlePurchasePaymentRecorded(
            fiscalPeriodRepository: app(FiscalPeriodRepositoryInterface::class),
            createJournalEntryService: app(CreateJournalEntryServiceInterface::class),
            createApTransactionService: app(CreateApTransactionServiceInterface::class),
            apTransactionRepository: app(ApTransactionRepositoryInterface::class),
        );
    }

    private function makeSalesPaymentListener(): HandleSalesPaymentRecorded
    {
        return new HandleSalesPaymentRecorded(
            fiscalPeriodRepository: app(FiscalPeriodRepositoryInterface::class),
            createJournalEntryService: app(CreateJournalEntryServiceInterface::class),
            createArTransactionService: app(CreateArTransactionServiceInterface::class),
            arTransactionRepository: app(ArTransactionRepositoryInterface::class),
        );
    }

    private function makePurchaseReturnListener(): HandlePurchaseReturnPosted
    {
        return new HandlePurchaseReturnPosted(
            fiscalPeriodRepository: app(FiscalPeriodRepositoryInterface::class),
            createJournalEntryService: app(CreateJournalEntryServiceInterface::class),
            createApTransactionService: app(CreateApTransactionServiceInterface::class),
            apTransactionRepository: app(ApTransactionRepositoryInterface::class),
        );
    }

    private function makeSalesReturnListener(): HandleSalesReturnReceived
    {
        return new HandleSalesReturnReceived(
            fiscalPeriodRepository: app(FiscalPeriodRepositoryInterface::class),
            createJournalEntryService: app(CreateJournalEntryServiceInterface::class),
            createArTransactionService: app(CreateArTransactionServiceInterface::class),
            arTransactionRepository: app(ArTransactionRepositoryInterface::class),
        );
    }

    private function makeApListener(): HandlePurchaseInvoiceApproved
    {
        return new HandlePurchaseInvoiceApproved(
            fiscalPeriodRepository: app(FiscalPeriodRepositoryInterface::class),
            createJournalEntryService: app(CreateJournalEntryServiceInterface::class),
            createApTransactionService: app(CreateApTransactionServiceInterface::class),
            apTransactionRepository: app(ApTransactionRepositoryInterface::class),
        );
    }

    private function makeArListener(): HandleSalesInvoicePosted
    {
        return new HandleSalesInvoicePosted(
            fiscalPeriodRepository: app(FiscalPeriodRepositoryInterface::class),
            createJournalEntryService: app(CreateJournalEntryServiceInterface::class),
        );
    }

    private function makePayrollListener(): HandlePayrollRunApproved
    {
        return new HandlePayrollRunApproved(
            fiscalPeriodRepository: app(FiscalPeriodRepositoryInterface::class),
            createJournalEntryService: app(CreateJournalEntryServiceInterface::class),
        );
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function makePayrollRun(
        ?array $metadata = null,
        string $totalGross = '1000.000000',
        string $totalNet = '800.000000',
        string $totalDeductions = '200.000000',
    ): PayrollRun {
        return new PayrollRun(
            tenantId: $this->tenantId,
            periodStart: new \DateTimeImmutable('2026-01-01'),
            periodEnd: new \DateTimeImmutable('2026-01-31'),
            status: PayrollRunStatus::APPROVED,
            processedAt: new \DateTimeImmutable('2026-01-31 09:00:00'),
            approvedAt: new \DateTimeImmutable('2026-01-31 10:00:00'),
            approvedBy: 1,
            totalGross: $totalGross,
            totalDeductions: $totalDeductions,
            totalNet: $totalNet,
            metadata: $metadata ?? [
                'payroll_expense_account_id' => $this->payrollExpenseAccountId,
                'payroll_liability_account_id' => $this->payrollLiabilityAccountId,
                'payroll_deductions_account_id' => $this->payrollDeductionsAccountId,
                'currency_id' => 1,
                'exchange_rate' => 1,
            ],
            createdAt: new \DateTimeImmutable('2026-01-31 08:00:00'),
            updatedAt: new \DateTimeImmutable('2026-01-31 10:00:00'),
            id: 501,
        );
    }

    private function seedReferenceData(): void
    {
        DB::table('tenants')->insert([
            'id' => $this->tenantId,
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('currencies')->insert([
            'id' => 1,
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('suppliers')->insert([
            'id' => 1,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'user_id' => null,
            'supplier_code' => 'SUP001',
            'name' => 'Test Supplier',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => 1,
            'payment_terms_days' => 30,
            'ap_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => $this->customerId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'user_id' => null,
            'customer_code' => 'CUS001',
            'name' => 'Test Customer',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => 1,
            'credit_limit' => '0.000000',
            'payment_terms_days' => 30,
            'ar_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('units_of_measure')->insert([
            'id' => $this->uomId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'name' => 'Each',
            'symbol' => 'ea',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('products')->insert([
            'id' => $this->productId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'category_id' => null,
            'brand_id' => null,
            'type' => 'physical',
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-SKU-001',
            'description' => null,
            'image_path' => null,
            'base_uom_id' => $this->uomId,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'metadata' => null,
            'purchase_price' => null,
            'sales_price' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('warehouses')->insert([
            'id' => $this->warehouseId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'name' => 'Main Warehouse',
            'code' => 'WH-01',
            'image_path' => null,
            'type' => 'standard',
            'address_id' => null,
            'is_active' => true,
            'is_default' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouse_locations')->insert([
            'id' => $this->warehouseLocationId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'warehouse_id' => $this->warehouseId,
            'parent_id' => null,
            'name' => 'Default Bin',
            'code' => 'BIN-01',
            'path' => null,
            'depth' => 0,
            'type' => 'bin',
            'is_active' => true,
            'is_pickable' => true,
            'is_receivable' => true,
            'capacity' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'first_name' => 'System',
            'last_name' => 'User',
            'email' => 'system@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fiscal_years')->insert([
            'id' => $this->fiscalYearId,
            'tenant_id' => $this->tenantId,
            'name' => 'FY2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fiscal_periods')->insert([
            'id' => $this->fiscalPeriodId,
            'tenant_id' => $this->tenantId,
            'fiscal_year_id' => $this->fiscalYearId,
            'period_number' => 1,
            'name' => 'Jan 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accounts = [
            ['id' => $this->apAccountId, 'code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['id' => $this->inventoryAccountId, 'code' => '1500', 'name' => 'Inventory', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['id' => $this->arAccountId, 'code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['id' => $this->revenueAccountId, 'code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'normal_balance' => 'credit'],
            ['id' => $this->cashAccountId, 'code' => '1000', 'name' => 'Cash and Bank', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['id' => $this->payrollExpenseAccountId, 'code' => '6100', 'name' => 'Payroll Expense', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['id' => $this->payrollLiabilityAccountId, 'code' => '2100', 'name' => 'Payroll Liability', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['id' => $this->payrollDeductionsAccountId, 'code' => '2200', 'name' => 'Payroll Deductions Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
        ];

        foreach ($accounts as $account) {
            DB::table('accounts')->insert(array_merge($account, [
                'tenant_id' => $this->tenantId,
                'parent_id' => null,
                'sub_type' => null,
                'is_system' => false,
                'is_bank_account' => false,
                'is_credit_card' => false,
                'currency_id' => null,
                'description' => null,
                'is_active' => true,
                'path' => null,
                'depth' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        DB::table('payment_methods')->insert([
            'id' => $this->paymentMethodId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'name' => 'Bank Transfer',
            'type' => 'bank_transfer',
            'account_id' => $this->cashAccountId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
