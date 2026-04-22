<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Contracts\CreateApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Listeners\HandlePurchaseInvoiceApproved;
use Modules\Finance\Infrastructure\Listeners\HandleSalesInvoicePosted;
use Modules\Purchase\Domain\Events\PurchaseInvoiceApproved;
use Modules\Sales\Domain\Events\SalesInvoicePosted;
use Tests\TestCase;

class FinanceListenerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $apAccountId = 1;

    private int $inventoryAccountId = 2;

    private int $arAccountId = 3;

    private int $revenueAccountId = 4;

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
    }
}
