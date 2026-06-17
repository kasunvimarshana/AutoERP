<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Audit\Models\AuditLogModel;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceFiscalPeriod;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceBalance;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Models\PaymentLine;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Services\FastPurchaseService;
use Modules\Supplier\Models\Supplier;
use Tests\TestCase;

final class FastPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_grn_only_receipt_creates_inventory_and_finance_without_invoice_or_payment(): void
    {
        $context = $this->context();

        $result = app(FastPurchaseService::class)->create($this->payload($context, [
            'supplier_reference' => 'FP-GRN-ONLY',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => false, 'record_payment_now' => false],
        ]));

        $this->assertNotNull($result['documents']['goods_receipt']);
        $this->assertNull($result['documents']['supplier_invoice']);
        $this->assertNull($result['documents']['supplier_payment']);
        $this->assertSame(1, GoodsReceiptNote::query()->count());
        $this->assertSame(1, InventoryMovement::query()->count());
        $this->assertSame('5.000000', (string) InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
        $this->assertSame(1, FinanceJournalEntry::query()->count());
        $this->assertSame(1, AuditLogModel::query()->where('event', 'fast_purchase.completed')->count());
    }

    public function test_credit_purchase_creates_grn_invoice_relationship_and_balance(): void
    {
        $context = $this->context();

        $result = app(FastPurchaseService::class)->create($this->payload($context, [
            'supplier_reference' => 'FP-CREDIT',
        ]));

        $this->assertNotNull($result['documents']['goods_receipt']);
        $this->assertNotNull($result['documents']['supplier_invoice']);
        $this->assertSame('500.000000', $result['summary']['grand_total']);
        $invoice = Invoice::query()->firstOrFail();
        $this->assertSame('500.000000', (string) $invoice->balance_due);
        $this->assertSame(1, PurchaseInvoiceLink::query()->where('invoice_id', $invoice->getKey())->count());
        $this->assertSame(1, FinanceJournalEntry::query()->count());
    }

    public function test_cash_purchase_allocates_payment_and_clears_invoice_balance(): void
    {
        $context = $this->context();

        $result = app(FastPurchaseService::class)->create($this->payload($context, [
            'supplier_reference' => 'FP-CASH',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => true, 'record_payment_now' => true],
            'payment' => [
                'amount' => '500.000000',
                'payment_method_id' => $context['cash_method_id'],
                'source_account_id' => $context['cash_account_id'],
                'reference' => 'PAY-FP-CASH',
            ],
        ]));

        $this->assertNotNull($result['documents']['supplier_payment']);
        $this->assertSame('500.000000', $result['summary']['paid_total']);
        $this->assertSame('0.000000', $result['summary']['balance_due']);
        $this->assertSame('0.000000', (string) Invoice::query()->firstOrFail()->balance_due);
        $this->assertSame(1, PaymentAllocation::query()->count());
        $this->assertSame('500.000000', (string) Payment::query()->firstOrFail()->allocated_amount);
        $this->assertSame(2, FinanceJournalEntry::query()->count());
    }

    public function test_direct_non_stock_purchase_has_invoice_payment_and_no_inventory(): void
    {
        $context = $this->context();

        $result = app(FastPurchaseService::class)->create($this->payload($context, [
            'supplier_reference' => 'FP-DIRECT',
            'warehouse_id' => null,
            'options' => ['receive_stock_now' => false, 'create_supplier_invoice_now' => true, 'record_payment_now' => true],
            'lines' => [[
                'item_id' => $context['expense_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '2.000000',
                'unit_cost' => '75.000000',
                'discount_amount' => '0.000000',
            ]],
            'payment' => [
                'amount' => '150.000000',
                'payment_method_id' => $context['cash_method_id'],
                'source_account_id' => $context['cash_account_id'],
            ],
        ]));

        $this->assertNull($result['documents']['goods_receipt']);
        $this->assertNotNull($result['documents']['supplier_invoice']);
        $this->assertNotNull($result['documents']['supplier_payment']);
        $this->assertSame(0, InventoryMovement::query()->count());
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_mixed_lines_receive_only_stock_and_invoice_all_lines(): void
    {
        $context = $this->context();

        app(FastPurchaseService::class)->create($this->payload($context, [
            'supplier_reference' => 'FP-MIXED',
            'lines' => [
                ['item_id' => $context['stock_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '3.000000', 'unit_cost' => '100.000000', 'discount_amount' => '0.000000'],
                ['item_id' => $context['expense_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '1.000000', 'unit_cost' => '40.000000', 'discount_amount' => '0.000000'],
            ],
        ]));

        $this->assertSame(1, GoodsReceiptNote::query()->firstOrFail()->lines()->count());
        $this->assertSame(1, InventoryMovement::query()->count());
        $this->assertSame(2, Invoice::query()->firstOrFail()->lines()->count());
    }

    public function test_uom_conversion_tax_withholding_partial_and_multiple_payment_lines(): void
    {
        $context = $this->context();
        $boxUomId = $this->uom($context['tenant_id'], 'BOX');
        DB::table('item_units')->insert([
            'tenant_id' => $context['tenant_id'],
            'item_id' => $context['stock_item_id'],
            'uom_id' => $boxUomId,
            'unit_role' => 'purchase',
            'conversion_factor' => '12.000000',
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $taxGroupId = $this->taxGroup($context['tenant_id']);

        $result = app(FastPurchaseService::class)->create($this->payload($context, [
            'supplier_reference' => 'FP-UOM-TAX',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => true, 'record_payment_now' => true],
            'lines' => [[
                'item_id' => $context['stock_item_id'],
                'uom_id' => $boxUomId,
                'quantity' => '2.000000',
                'unit_cost' => '100.000000',
                'discount_amount' => '0.000000',
                'tax_group_id' => $taxGroupId,
            ]],
            'payment' => [
                'lines' => [
                    ['amount' => '50.000000', 'payment_method_id' => $context['cash_method_id'], 'source_account_id' => $context['cash_account_id']],
                    ['amount' => '25.000000', 'payment_method_id' => $context['bank_method_id'], 'source_account_id' => $context['bank_account_id']],
                ],
            ],
        ]));

        $this->assertSame('20.000000', $result['summary']['tax_total']);
        $this->assertSame('10.000000', $result['summary']['withholding_total']);
        $this->assertSame('210.000000', $result['summary']['grand_total']);
        $this->assertSame('75.000000', $result['summary']['paid_total']);
        $this->assertSame('135.000000', $result['summary']['balance_due']);
        $this->assertSame('24.000000', (string) InventoryMovement::query()->firstOrFail()->quantity);
        $this->assertSame(2, PaymentLine::query()->count());
        $this->assertSame('135.000000', (string) InvoiceBalance::query()->firstOrFail()->remaining_amount);
    }

    public function test_supplier_reference_makes_submission_idempotent_and_rejects_different_payloads(): void
    {
        $context = $this->context();
        $payload = $this->payload($context, ['supplier_reference' => 'FP-IDEMPOTENT']);
        $first = app(FastPurchaseService::class)->create($payload);
        $second = app(FastPurchaseService::class)->create($payload);

        $this->assertSame($first['documents']['goods_receipt']['id'], $second['documents']['goods_receipt']['id']);
        $this->assertSame(1, GoodsReceiptNote::query()->count());
        $this->assertSame(1, Invoice::query()->count());

        $this->expectException(InvalidArgumentException::class);
        app(FastPurchaseService::class)->create($this->payload($context, [
            'supplier_reference' => 'FP-IDEMPOTENT',
            'lines' => [[
                'item_id' => $context['stock_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '1.000000',
                'unit_cost' => '100.000000',
                'discount_amount' => '0.000000',
            ]],
        ]));
    }

    public function test_transaction_rolls_back_when_finance_posting_fails(): void
    {
        $context = $this->context();
        $this->app->instance(FinancePostingInterface::class, new class implements FinancePostingInterface
        {
            public function createDraftJournal(PostingContext $request): PostingResultData
            {
                throw new InvalidArgumentException('Posting failed.');
            }

            public function validatePosting(PostingContext $request): void {}

            public function post(PostingContext $request, ?int $postedBy = null): PostingResultData
            {
                throw new InvalidArgumentException('Posting failed.');
            }

            public function postJournal(int $journalId, ?int $postedBy = null): PostingResultData
            {
                throw new InvalidArgumentException('Posting failed.');
            }

            public function reverseJournal(int $journalId, string $reversalDate, ?int $reversedBy = null, ?string $reason = null): PostingResultData
            {
                throw new InvalidArgumentException('Posting failed.');
            }
        });

        try {
            app(FastPurchaseService::class)->create($this->payload($context, ['supplier_reference' => 'FP-ROLLBACK']));
            $this->fail('Expected posting failure.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Posting failed.', $exception->getMessage());
        }

        $this->assertSame(0, GoodsReceiptNote::query()->count());
        $this->assertSame(0, InventoryMovement::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(0, AuditLogModel::query()->where('event', 'fast_purchase.completed')->count());
    }

    public function test_scope_and_client_authority_validation(): void
    {
        $context = $this->context();
        $otherTenantId = $this->tenant('OTHER');
        $otherSupplierId = $this->supplier($otherTenantId, 'SUP-OTHER');

        $this->expectException(InvalidArgumentException::class);
        app(FastPurchaseService::class)->preview($this->payload($context, [
            'supplier_id' => $otherSupplierId,
            'grand_total' => '1.000000',
        ]));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $context, array $overrides = []): array
    {
        return array_replace_recursive([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => null,
            'current_user_id' => null,
            'supplier_id' => $context['supplier_id'],
            'supplier_reference' => 'FP-'.Str::upper(Str::random(8)),
            'purchase_date' => '2026-06-16',
            'warehouse_id' => $context['warehouse_id'],
            'currency_id' => null,
            'exchange_rate' => '1.000000',
            'payment_terms' => 'net_30',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => true, 'record_payment_now' => false],
            'lines' => [[
                'item_id' => $context['stock_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '5.000000',
                'unit_cost' => '100.000000',
                'discount_amount' => '0.000000',
            ]],
        ], $overrides);
    }

    /**
     * @return array<string, int>
     */
    private function context(): array
    {
        $tenantId = $this->tenant();
        $uomId = $this->uom($tenantId, 'PCS');
        $supplierId = $this->supplier($tenantId, 'SUP-FP');
        $warehouseId = $this->warehouse($tenantId, 'WH-FP');
        $stock = $this->item($tenantId, 'ITEM-STOCK', ItemType::Stock, true, $uomId);
        $expense = $this->item($tenantId, 'ITEM-EXP', ItemType::Service, false, $uomId);
        $accounts = $this->finance($tenantId);
        $cashMethodId = $this->paymentMethod($tenantId, 'CASH', 'cash');
        $bankMethodId = $this->paymentMethod($tenantId, 'BANK', 'bank_transfer');

        return [
            'tenant_id' => $tenantId,
            'uom_id' => $uomId,
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'stock_item_id' => (int) $stock->getKey(),
            'expense_item_id' => (int) $expense->getKey(),
            'cash_account_id' => $accounts['cash'],
            'bank_account_id' => $accounts['bank'],
            'cash_method_id' => $cashMethodId,
            'bank_method_id' => $bankMethodId,
        ];
    }

    private function tenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-FP-'.$suffix,
            'name' => 'Fast Purchase '.$suffix,
            'slug' => 'fast-purchase-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function uom(int $tenantId, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => 'Unit '.$code,
            'symbol' => Str::lower($code),
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function supplier(int $tenantId, string $code): int
    {
        return (int) Supplier::query()->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_number' => $code,
            'code' => $code,
            'name' => 'Supplier '.$code,
            'display_name' => 'Supplier '.$code,
            'supplier_type' => 'local',
            'status' => 'active',
            'is_credit_allowed' => true,
            'is_advance_allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function warehouse(int $tenantId, string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Warehouse '.$code,
            'code' => $code,
            'type' => 'standard',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function item(int $tenantId, string $code, ItemType $type, bool $stockable, int $uomId): Item
    {
        return app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            code: $code.'-'.Str::upper(Str::random(4)),
            name: 'Fast '.$code,
            itemType: $type,
            trackingType: TrackingType::None,
            costingMethod: $stockable ? CostingMethod::Fifo : CostingMethod::None,
            baseUomId: $uomId,
            isStockable: $stockable,
        ));
    }

    /**
     * @return array{cash: int, bank: int}
     */
    private function finance(int $tenantId): array
    {
        $asset = $this->accountType($tenantId, 'ASSET', NormalBalance::Debit, StatementType::BalanceSheet);
        $liability = $this->accountType($tenantId, 'LIABILITY', NormalBalance::Credit, StatementType::BalanceSheet);
        $expense = $this->accountType($tenantId, 'EXPENSE', NormalBalance::Debit, StatementType::IncomeStatement);
        $cash = $this->account($tenantId, $asset, '1010', 'Cash', NormalBalance::Debit, cash: true);
        $bank = $this->account($tenantId, $asset, '1020', 'Bank', NormalBalance::Debit, bank: true);
        $inventory = $this->account($tenantId, $asset, '1200', 'Inventory', NormalBalance::Debit);
        $tax = $this->account($tenantId, $asset, '1300', 'Input Tax', NormalBalance::Debit, tax: true);
        $payable = $this->account($tenantId, $liability, '2100', 'Payable', NormalBalance::Credit);
        $purchaseExpense = $this->account($tenantId, $expense, '5100', 'Purchase Expense', NormalBalance::Debit);

        $this->profile($tenantId, 'inventory_receipt', ['inventory' => $inventory, 'payable' => $payable]);
        $this->profile($tenantId, 'purchase_invoice', ['expense' => $purchaseExpense, 'payable' => $payable, 'tax_receivable' => $tax]);
        $this->profile($tenantId, 'payment_made', ['payable' => $payable]);

        $year = FinanceFiscalYear::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
        FinanceFiscalPeriod::query()->create([
            'tenant_id' => $tenantId,
            'fiscal_year_id' => $year->getKey(),
            'name' => 'June 2026',
            'period_number' => 6,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
        ]);

        return ['cash' => (int) $cash->getKey(), 'bank' => (int) $bank->getKey()];
    }

    private function accountType(int $tenantId, string $code, NormalBalance $normalBalance, StatementType $statementType): FinanceAccountType
    {
        return FinanceAccountType::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'normal_balance' => $normalBalance->value,
            'statement_type' => $statementType->value,
            'is_active' => true,
        ]);
    }

    private function account(int $tenantId, FinanceAccountType $type, string $code, string $name, NormalBalance $normalBalance, bool $cash = false, bool $bank = false, bool $tax = false): FinanceAccount
    {
        return app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $type->getKey(),
            code: $code,
            name: $name,
            normalBalance: $normalBalance,
            isCashAccount: $cash,
            isBankAccount: $bank,
            isTaxAccount: $tax,
        ));
    }

    /**
     * @param  array<string, FinanceAccount>  $rules
     */
    private function profile(int $tenantId, string $code, array $rules): void
    {
        $profile = FinancePostingProfile::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'is_active' => true,
        ]);
        foreach ($rules as $key => $account) {
            FinancePostingProfileRule::query()->create([
                'posting_profile_id' => $profile->getKey(),
                'line_key' => $key,
                'account_id' => $account->getKey(),
            ]);
        }
    }

    private function paymentMethod(int $tenantId, string $code, string $type): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'method_type' => $type,
            'direction_allowed' => 'outbound',
            'requires_reference' => false,
            'requires_bank_account' => $type === 'bank_transfer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function taxGroup(int $tenantId): int
    {
        $vat = DB::table('taxes')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'VAT-'.Str::upper(Str::random(4)),
            'name' => 'VAT',
            'tax_type' => 'vat',
            'calculation_method' => 'percentage',
            'is_withholding' => false,
            'recoverable' => true,
            'payable' => false,
            'receivable' => true,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $wht = DB::table('taxes')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'WHT-'.Str::upper(Str::random(4)),
            'name' => 'WHT',
            'tax_type' => 'withholding',
            'calculation_method' => 'percentage',
            'is_withholding' => true,
            'recoverable' => false,
            'payable' => true,
            'receivable' => false,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tax_rates')->insert([
            ['tax_id' => $vat, 'rate' => '10.000000', 'effective_from' => '2026-01-01', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tax_id' => $wht, 'rate' => '5.000000', 'effective_from' => '2026-01-01', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $group = DB::table('tax_groups')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'TG-'.Str::upper(Str::random(4)),
            'name' => 'Purchase Tax',
            'is_default' => false,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tax_group_lines')->insert([
            ['tax_group_id' => $group, 'tax_id' => $vat, 'sequence' => 1, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tax_group_id' => $group, 'tax_id' => $wht, 'sequence' => 2, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        return (int) $group;
    }
}
