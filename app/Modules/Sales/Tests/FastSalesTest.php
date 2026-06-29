<?php

declare(strict_types=1);

namespace Modules\Sales\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Audit\Models\AuditLog;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\CurrentTenantContext;
use Modules\Core\DTOs\CurrentUserContext;
use Modules\Core\DTOs\DataRecord;
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
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Services\ItemPriceScopeKey;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceBalance;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Models\PaymentLine;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesInvoiceLink;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\FastSalesService;
use Tests\TestCase;

final class FastSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_only_sale_creates_sales_order_without_inventory_invoice_payment_or_finance(): void
    {
        $context = $this->context();

        $result = $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-ORDER',
            'options' => [
                'create_sales_order_only' => true,
                'deliver_items_now' => false,
                'create_customer_invoice_now' => false,
                'record_customer_receipt_now' => false,
            ],
        ]));

        $this->assertNotNull($result['documents']['sales_order']);
        $this->assertNull($result['documents']['goods_delivery']);
        $this->assertNull($result['documents']['customer_invoice']);
        $this->assertNull($result['documents']['customer_receipt']);
        $this->assertSame(1, SalesOrder::query()->count());
        $this->assertSame(0, SalesDelivery::query()->count());
        $this->assertSame(0, InventoryMovement::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(0, FinanceJournalEntry::query()->count());
        $this->assertSame(1, AuditLog::query()->where('event_name', 'sales.fast_sales.completed')->count());
    }

    public function test_delivery_only_sale_creates_delivery_inventory_issue_and_finance_without_invoice_or_receipt(): void
    {
        $context = $this->context();
        $this->seedStock($context, '10.000000');

        $result = $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-DELIVERY',
            'options' => [
                'create_sales_order_only' => false,
                'deliver_items_now' => true,
                'create_customer_invoice_now' => false,
                'record_customer_receipt_now' => false,
            ],
        ]));

        $this->assertNull($result['documents']['sales_order']);
        $this->assertNotNull($result['documents']['goods_delivery']);
        $this->assertNull($result['documents']['customer_invoice']);
        $this->assertNull($result['documents']['customer_receipt']);
        $this->assertSame(1, SalesDelivery::query()->count());
        $this->assertSame(1, InventoryMovement::query()->where('source_type', 'sales_delivery')->count());
        $this->assertSame('5.000000', (string) InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
        $this->assertSame(1, FinanceJournalEntry::query()->count());
    }

    public function test_credit_sale_creates_delivery_invoice_links_balance_and_finance(): void
    {
        $context = $this->context();
        $this->seedStock($context, '10.000000');

        $result = $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-CREDIT',
        ]));

        $this->assertNotNull($result['documents']['goods_delivery']);
        $this->assertNotNull($result['documents']['customer_invoice']);
        $invoice = Invoice::query()->firstOrFail();
        $delivery = SalesDelivery::query()->firstOrFail();

        $this->assertSame('500.000000', $result['summary']['grand_total']);
        $this->assertSame('500.000000', (string) $invoice->balance_due);
        $this->assertSame(1, SalesInvoiceLink::query()->where('invoice_id', $invoice->getKey())->count());
        $this->assertSame('5.000000', (string) $delivery->lines()->firstOrFail()->invoiced_quantity);
        $this->assertSame(2, FinanceJournalEntry::query()->count());
    }

    public function test_cash_sale_allocates_receipt_clears_balance_and_posts_finance(): void
    {
        $context = $this->context();
        $this->seedStock($context, '10.000000');

        $result = $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-CASH',
            'options' => [
                'create_sales_order_only' => false,
                'deliver_items_now' => true,
                'create_customer_invoice_now' => true,
                'record_customer_receipt_now' => true,
            ],
            'payment' => [
                'amount' => '500.000000',
                'payment_method_id' => $context['cash_method_id'],
                'reference' => 'FS-CASH-REC',
            ],
        ]));

        $this->assertNotNull($result['documents']['customer_receipt']);
        $this->assertSame('500.000000', $result['summary']['received_total']);
        $this->assertSame('0.000000', $result['summary']['balance_due']);
        $this->assertSame('0.000000', (string) Invoice::query()->firstOrFail()->balance_due);
        $this->assertSame(1, PaymentAllocation::query()->count());
        $payment = Payment::query()->firstOrFail();
        $this->assertSame('500.000000', (string) $payment->allocated_amount);
        $this->assertSame(PaymentDocumentStatus::Approved, $payment->document_status);
        $this->assertSame(PaymentPostingStatus::Posted, $payment->posting_status);
        $this->assertSame(PaymentAllocationState::FullyAllocated, $payment->allocation_status);
        $this->assertSame(3, FinanceJournalEntry::query()->count());
    }

    public function test_direct_non_stock_sale_has_invoice_receipt_and_no_inventory(): void
    {
        $context = $this->context();

        $result = $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-DIRECT',
            'warehouse_id' => null,
            'options' => [
                'create_sales_order_only' => false,
                'deliver_items_now' => false,
                'create_customer_invoice_now' => true,
                'record_customer_receipt_now' => true,
            ],
            'lines' => [[
                'item_id' => $context['service_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '2.000000',
                'discount_amount' => '0.000000',
            ]],
            'payment' => [
                'amount' => '150.000000',
                'payment_method_id' => $context['cash_method_id'],
            ],
        ]));

        $this->assertNull($result['documents']['goods_delivery']);
        $this->assertNotNull($result['documents']['customer_invoice']);
        $this->assertNotNull($result['documents']['customer_receipt']);
        $this->assertSame(0, InventoryMovement::query()->count());
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(2, FinanceJournalEntry::query()->count());
    }

    public function test_mixed_stock_and_non_stock_sale_delivers_only_stock_and_invoices_all_lines(): void
    {
        $context = $this->context();
        $this->seedStock($context, '10.000000');

        $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-MIXED',
            'lines' => [
                ['item_id' => $context['stock_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '3.000000', 'discount_amount' => '0.000000'],
                ['item_id' => $context['service_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '1.000000', 'discount_amount' => '0.000000'],
            ],
        ]));

        $this->assertSame(1, SalesDelivery::query()->firstOrFail()->lines()->count());
        $this->assertSame(1, InventoryMovement::query()->where('source_type', 'sales_delivery')->count());
        $this->assertSame(2, Invoice::query()->firstOrFail()->lines()->count());
    }

    public function test_uom_conversion_tax_withholding_partial_and_multiple_receipt_lines(): void
    {
        $context = $this->context();
        $boxUomId = $this->uom($context['tenant_id'], 'BOX');
        DB::table('item_units')->insert([
            'tenant_id' => $context['tenant_id'],
            'item_id' => $context['stock_item_id'],
            'uom_id' => $boxUomId,
            'unit_role' => 'sales',
            'conversion_factor' => '12.000000',
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->price($context['tenant_id'], $context['stock_item_id'], '120.000000', 'sales', $boxUomId, $context['currency_id']);
        $this->seedStock($context, '24.000000');
        $taxGroupId = $this->taxGroup($context['tenant_id'], $context['withholding_receivable_account_id']);

        $result = $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-UOM-TAX',
            'lines' => [[
                'item_id' => $context['stock_item_id'],
                'uom_id' => $boxUomId,
                'quantity' => '2.000000',
                'discount_amount' => '0.000000',
                'tax_group_id' => $taxGroupId,
            ]],
            'options' => [
                'create_sales_order_only' => false,
                'deliver_items_now' => true,
                'create_customer_invoice_now' => true,
                'record_customer_receipt_now' => true,
            ],
            'payment' => [
                'lines' => [
                    ['amount' => '100.000000', 'payment_method_id' => $context['cash_method_id']],
                    ['amount' => '50.000000', 'payment_method_id' => $context['bank_method_id']],
                ],
            ],
        ]));

        $this->assertSame('24.000000', $result['summary']['tax_total']);
        $this->assertSame('12.000000', $result['summary']['withholding_total']);
        $this->assertSame('252.000000', $result['summary']['grand_total']);
        $this->assertSame('150.000000', $result['summary']['received_total']);
        $this->assertSame('102.000000', $result['summary']['balance_due']);
        $this->assertSame('24.000000', (string) InventoryMovement::query()->firstOrFail()->quantity);
        $this->assertSame(2, PaymentLine::query()->count());
        $this->assertSame('102.000000', (string) InvoiceBalance::query()->firstOrFail()->remaining_amount);
        $this->assertSame(4, FinanceJournalEntry::query()->count());
    }

    public function test_insufficient_stock_is_rejected_by_preview_and_submission(): void
    {
        $context = $this->context();

        try {
            $this->previewFastSale($this->payload($context, [
                'customer_reference' => 'FS-NOSTOCK',
            ]));
            $this->fail('Expected insufficient stock validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Insufficient stock is available for delivery.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-NOSTOCK-CREATE',
            'options' => [
                'create_sales_order_only' => false,
                'deliver_items_now' => true,
                'create_customer_invoice_now' => false,
                'record_customer_receipt_now' => false,
            ],
        ]));
    }

    public function test_customer_credit_limit_and_price_override_are_enforced(): void
    {
        $context = $this->context([
            'credit_limit' => '100.000000',
            'allow_over_credit' => false,
        ]);
        $this->seedStock($context, '10.000000');

        $this->expectException(InvalidArgumentException::class);
        $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-CREDIT-LIMIT',
            'lines' => [[
                'item_id' => $context['stock_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '2.000000',
                'unit_price' => '999.000000',
                'discount_amount' => '0.000000',
            ]],
        ]));
    }

    public function test_idempotency_key_replays_same_payload_and_rejects_different_payloads(): void
    {
        $context = $this->context();
        $this->seedStock($context, '10.000000');
        $payload = $this->payload($context, [
            'customer_reference' => 'FS-IDEMPOTENT',
            'idempotency_key' => 'idem-fast-sales-test',
        ]);
        $first = $this->createFastSale($payload);
        $second = $this->createFastSale($payload);

        $this->assertSame($first['documents']['goods_delivery']['id'], $second['documents']['goods_delivery']['id']);
        $this->assertSame(1, SalesDelivery::query()->count());
        $this->assertSame(1, Invoice::query()->count());

        $this->expectException(InvalidArgumentException::class);
        $this->createFastSale($this->payload($context, [
            'customer_reference' => 'FS-IDEMPOTENT',
            'idempotency_key' => 'idem-fast-sales-test',
            'lines' => [[
                'item_id' => $context['stock_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '1.000000',
                'discount_amount' => '0.000000',
            ]],
        ]));
    }

    public function test_transaction_rolls_back_when_finance_posting_fails(): void
    {
        $context = $this->context();
        $this->seedStock($context, '10.000000');
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
            $this->createFastSale($this->payload($context, ['customer_reference' => 'FS-ROLLBACK']));
            $this->fail('Expected posting failure.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Posting failed.', $exception->getMessage());
        }

        $this->assertSame(0, SalesOrder::query()->count());
        $this->assertSame(0, SalesDelivery::query()->count());
        $this->assertSame(0, InventoryMovement::query()->where('source_type', 'sales_delivery')->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(0, AuditLog::query()->where('event_name', 'sales.fast_sales.completed')->count());
    }

    public function test_scope_and_client_authority_fields_are_rejected(): void
    {
        $context = $this->context();
        $otherTenantId = $this->tenant('OTHER');
        $otherCustomerId = $this->customer($otherTenantId, 'CUS-OTHER');

        $this->expectException(InvalidArgumentException::class);
        $this->previewFastSale($this->payload($context, [
            'customer_id' => $otherCustomerId,
            'grand_total' => '1.000000',
        ]));
    }

    public function test_internal_payment_finance_fields_are_rejected(): void
    {
        $context = $this->context();

        $this->expectException(InvalidArgumentException::class);
        $this->previewFastSale($this->payload($context, [
            'options' => [
                'create_sales_order_only' => false,
                'deliver_items_now' => true,
                'create_customer_invoice_now' => true,
                'record_customer_receipt_now' => true,
            ],
            'payment' => [
                'amount' => '500.000000',
                'payment_method_id' => $context['cash_method_id'],
                'destination_account_id' => $context['cash_account_id'],
            ],
        ]));
    }

    public function test_organization_scope_is_enforced_for_customer_references(): void
    {
        $tenantId = $this->tenant('ORG');
        $this->useTenantContext($tenantId);
        $this->timezone();
        $currencyId = $this->currency('LKR');
        DB::table('tenants')->where('id', $tenantId)->update(['base_currency_id' => $currencyId]);
        $organizationUnitId = $this->organizationUnit($tenantId, 'ORG-A');
        $otherOrganizationUnitId = $this->organizationUnit($tenantId, 'ORG-B');
        $uomId = $this->uom($tenantId, 'PCS-ORG');
        $warehouseId = $this->warehouse($tenantId, 'WH-ORG', $organizationUnitId);
        $customerId = $this->customer($tenantId, 'CUS-ORG-A', $organizationUnitId);
        $otherCustomerId = $this->customer($tenantId, 'CUS-ORG-B', $otherOrganizationUnitId);
        $stockItemId = $this->item($tenantId, 'ITEM-ORG', 'stock', true, $uomId, $organizationUnitId);
        $accounts = $this->finance($tenantId, $organizationUnitId);
        $this->price($tenantId, $stockItemId, '100.000000', 'sales', $uomId, $currencyId, $organizationUnitId);
        $this->seedStock([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'uom_id' => $uomId,
            'stock_item_id' => $stockItemId,
        ], '10.000000');

        $this->expectException(InvalidArgumentException::class);
        $this->previewFastSale([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $otherCustomerId,
            'customer_reference' => 'FS-ORG',
            'transaction_date' => '2026-06-16',
            'warehouse_id' => $warehouseId,
            'currency_id' => $currencyId,
            'exchange_rate' => '1.000000',
            'payment_terms' => 'net_30',
            'options' => [
                'create_sales_order_only' => false,
                'deliver_items_now' => true,
                'create_customer_invoice_now' => true,
                'record_customer_receipt_now' => false,
            ],
            'lines' => [[
                'item_id' => $stockItemId,
                'uom_id' => $uomId,
                'quantity' => '1.000000',
                'discount_amount' => '0.000000',
            ]],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function createFastSale(array $payload): array
    {
        return app(FastSalesService::class)->create($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function previewFastSale(array $payload): array
    {
        return app(FastSalesService::class)->preview($payload);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $context, array $overrides = []): array
    {
        return array_replace_recursive([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'] ?? null,
            'current_user_id' => null,
            'idempotency_key' => 'idem-'.Str::lower((string) Str::uuid()),
            'customer_id' => $context['customer_id'],
            'customer_reference' => 'FS-'.Str::upper(Str::random(8)),
            'transaction_date' => '2026-06-16',
            'warehouse_id' => $context['warehouse_id'],
            'currency_id' => $context['currency_id'],
            'exchange_rate' => '1.000000',
            'payment_terms' => 'net_30',
            'options' => [
                'create_sales_order_only' => false,
                'deliver_items_now' => true,
                'create_customer_invoice_now' => true,
                'record_customer_receipt_now' => false,
            ],
            'lines' => [[
                'item_id' => $context['stock_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '5.000000',
                'discount_amount' => '0.000000',
            ]],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function context(array $overrides = []): array
    {
        $tenantId = $this->tenant();
        $this->useTenantContext($tenantId);
        $this->timezone();
        $currencyId = $this->currency('LKR');
        DB::table('tenants')->where('id', $tenantId)->update(['base_currency_id' => $currencyId]);
        $organizationUnitId = $this->nullableInt($overrides['organization_unit_id'] ?? null);
        $uomId = $this->uom($tenantId, 'PCS');
        $customerId = $this->customer($tenantId, 'CUS-FS', $organizationUnitId);
        $warehouseId = $this->warehouse($tenantId, 'WH-FS', $organizationUnitId);
        $stockItemId = $this->item($tenantId, 'ITEM-STOCK', 'stock', true, $uomId, $organizationUnitId);
        $serviceItemId = $this->item($tenantId, 'ITEM-SVC', 'service', false, $uomId, $organizationUnitId);
        $accounts = $this->finance($tenantId, $organizationUnitId);
        $cashMethodId = $this->paymentMethod($tenantId, 'CASH', 'cash', 'inbound');
        $bankMethodId = $this->paymentMethod($tenantId, 'BANK', 'bank_transfer', 'inbound');
        $this->price($tenantId, $stockItemId, '100.000000', 'sales', $uomId, $currencyId, $organizationUnitId);
        $this->price($tenantId, $serviceItemId, '75.000000', 'service', $uomId, $currencyId, $organizationUnitId);

        if (array_key_exists('credit_limit', $overrides)) {
            DB::table('customers')->where('id', $customerId)->update(['credit_limit' => $overrides['credit_limit']]);
            DB::table('customer_credit_profiles')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'customer_id' => $customerId,
                'credit_limit' => $overrides['credit_limit'],
                'credit_period_days' => 30,
                'warning_threshold_percent' => '80.000000',
                'allow_over_credit' => (bool) ($overrides['allow_over_credit'] ?? false),
                'allow_partial_payment' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'currency_id' => $currencyId,
            'uom_id' => $uomId,
            'customer_id' => $customerId,
            'warehouse_id' => $warehouseId,
            'stock_item_id' => $stockItemId,
            'service_item_id' => $serviceItemId,
            'cash_account_id' => $accounts['cash'],
            'bank_account_id' => $accounts['bank'],
            'withholding_receivable_account_id' => $accounts['withholding_receivable'],
            'cash_method_id' => $cashMethodId,
            'bank_method_id' => $bankMethodId,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function seedStock(array $context, string $quantity): void
    {
        $adjustment = app(StockAdjustmentService::class)->create(new StockAdjustmentData(
            tenantId: (int) $context['tenant_id'],
            adjustmentDate: '2026-06-16',
            adjustmentType: AdjustmentType::OpeningBalance,
            warehouseId: (int) $context['warehouse_id'],
            reason: 'Fast sales opening stock',
            organizationUnitId: $context['organization_unit_id'] ?? null,
            lines: [new StockAdjustmentLineData(
                (int) $context['stock_item_id'],
                '0.000000',
                $quantity,
                $quantity,
                '40.000000',
            )],
        ));
        app(StockAdjustmentService::class)->post($adjustment);
    }

    private function tenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-FS-'.$suffix,
            'name' => 'Fast Sales '.$suffix,
            'slug' => 'fast-sales-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function useTenantContext(int $tenantId): void
    {
        $tenant = DB::table('tenants')->where('id', $tenantId)->first();
        if ($tenant === null) {
            throw new InvalidArgumentException('Fast Sales test tenant was not found.');
        }
        $tenantRecord = new DataRecord([
            'id' => (int) $tenant->id,
            'uuid' => (string) $tenant->uuid,
            'code' => (string) $tenant->code,
            'name' => (string) $tenant->name,
            'slug' => (string) $tenant->slug,
            'status' => (string) $tenant->status,
        ]);
        $currentTenant = new CurrentTenantContext(
            tenant: $tenantRecord,
            tenantId: (int) $tenant->id,
            tenantCode: (string) $tenant->code,
            tenantUuid: (string) $tenant->uuid,
            domain: null,
            status: (string) $tenant->status,
            applicationId: null,
            source: 'fast_sales_test',
        );

        $this->app->instance(TenantExecutionContextInterface::class, new class($tenantId) implements TenantExecutionContextInterface
        {
            public function __construct(private readonly int $tenantId) {}

            public function tenantId(): ?int
            {
                return $this->tenantId;
            }

            public function isActive(): bool
            {
                return true;
            }

            public function isControlPlane(): bool
            {
                return false;
            }

            public function runForTenant(int $tenantId, callable $callback): mixed
            {
                if ($tenantId !== $this->tenantId) {
                    throw new InvalidArgumentException('Nested tenant execution cannot switch tenant ownership.');
                }

                return $callback();
            }

            public function runAsControlPlane(callable $callback): mixed
            {
                throw new InvalidArgumentException('Tenant-scoped Fast Sales tests cannot enter control-plane execution.');
            }
        });
        $this->app->instance(CurrentTenantContextAccessorInterface::class, new class($currentTenant) implements CurrentTenantContextAccessorInterface
        {
            public function __construct(private readonly CurrentTenantContext $currentTenant) {}

            public function current(): ?CurrentTenantContext
            {
                return $this->currentTenant;
            }

            public function requireCurrent(): CurrentTenantContext
            {
                return $this->currentTenant;
            }

            public function currentTenant(): ?DataRecord
            {
                return $this->currentTenant->tenant();
            }

            public function currentTenantId(): ?int
            {
                return $this->currentTenant->tenantId();
            }

            public function currentTenantCode(): ?string
            {
                return $this->currentTenant->tenantCode();
            }

            public function currentTenantUuid(): ?string
            {
                return $this->currentTenant->tenantUuid();
            }

            public function currentTenantDomain(): ?string
            {
                return $this->currentTenant->domain();
            }

            public function currentApplicationId(): ?string
            {
                return $this->currentTenant->applicationId();
            }
        });
        $user = new class implements \Illuminate\Contracts\Auth\Authenticatable
        {
            public function getAuthIdentifierName(): string { return 'id'; }
            public function getAuthIdentifier(): int { return 1; }
            public function getAuthPasswordName(): string { return 'password'; }
            public function getAuthPassword(): string { return ''; }
            public function getRememberToken(): ?string { return null; }
            public function setRememberToken($value): void {}
            public function getRememberTokenName(): string { return 'remember_token'; }
        };
        $currentUser = new CurrentUserContext(
            user: $user,
            userId: 1,
            guard: 'test',
            provider: null,
            applicationId: null,
        );
        $this->app->instance(CurrentUserContextAccessorInterface::class, new class($currentUser) implements CurrentUserContextAccessorInterface
        {
            public function __construct(private readonly CurrentUserContext $currentUser) {}

            public function current(): ?CurrentUserContext
            {
                return $this->currentUser;
            }

            public function requireCurrent(): CurrentUserContext
            {
                return $this->currentUser;
            }

            public function currentUserId(): ?int
            {
                return $this->currentUser->userId();
            }

            public function currentApplicationId(): ?string
            {
                return $this->currentUser->applicationId();
            }
        });
    }

    private function organizationUnit(int $tenantId, string $code): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => $code,
            'code' => $code,
            'depth' => 0,
            'is_active' => true,
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

    private function currency(string $code): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => $code,
            'name' => $code.' Currency',
            'symbol' => $code,
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function timezone(): void
    {
        foreach (['UTC', 'Asia/Colombo'] as $timezone) {
            DB::table('timezones')->updateOrInsert(
                ['name' => $timezone],
                [
                    'display_name' => $timezone,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function customer(int $tenantId, string $code, ?int $organizationUnitId = null): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => $code,
            'code' => $code,
            'name' => 'Customer '.$code,
            'display_name' => 'Customer '.$code,
            'customer_type' => 'business',
            'status' => 'active',
            'is_credit_allowed' => true,
            'is_advance_allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function warehouse(int $tenantId, string $code, ?int $organizationUnitId = null): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
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

    private function item(int $tenantId, string $code, string $type, bool $stockable, int $uomId, ?int $organizationUnitId = null): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => 'Fast '.$code,
            'item_type' => $type,
            'tracking_type' => 'none',
            'costing_method' => 'fifo',
            'base_uom_id' => $uomId,
            'is_stockable' => $stockable,
            'is_combo' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function price(
        int $tenantId,
        int $itemId,
        string $amount,
        string $type,
        int $uomId,
        int $currencyId,
        ?int $organizationUnitId = null,
    ): void {
        DB::table('item_prices')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'item_id' => $itemId,
            'price_type' => $type,
            'currency_id' => $currencyId,
            'uom_id' => $uomId,
            'amount' => $amount,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
            'scope_key' => ItemPriceScopeKey::for($organizationUnitId, null, ItemPriceType::from($type), $currencyId, $uomId),
            'lineage_key' => (string) Str::uuid(),
            'revision_no' => 1,
            'recorded_from' => now(),
            'recorded_to' => null,
            'correction_reason' => 'Fast Sales test price fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{cash: int, bank: int, withholding_receivable: int}
     */
    private function finance(int $tenantId, ?int $organizationUnitId = null): array
    {
        $asset = $this->accountType($tenantId, 'ASSET', NormalBalance::Debit, StatementType::BalanceSheet);
        $liability = $this->accountType($tenantId, 'LIABILITY', NormalBalance::Credit, StatementType::BalanceSheet);
        $income = $this->accountType($tenantId, 'INCOME', NormalBalance::Credit, StatementType::IncomeStatement);
        $expense = $this->accountType($tenantId, 'EXPENSE', NormalBalance::Debit, StatementType::IncomeStatement);
        $cash = $this->account($tenantId, $organizationUnitId, $asset, '1010', 'Cash', NormalBalance::Debit, cash: true);
        $bank = $this->account($tenantId, $organizationUnitId, $asset, '1020', 'Bank', NormalBalance::Debit, bank: true);
        $receivable = $this->account($tenantId, $organizationUnitId, $asset, '1100', 'Receivable', NormalBalance::Debit);
        $inventory = $this->account($tenantId, $organizationUnitId, $asset, '1200', 'Inventory', NormalBalance::Debit);
        $withholdingReceivable = $this->account($tenantId, $organizationUnitId, $asset, '1300', 'Withholding Receivable', NormalBalance::Debit, tax: true);
        $taxPayable = $this->account($tenantId, $organizationUnitId, $liability, '2200', 'Tax Payable', NormalBalance::Credit, tax: true);
        $revenue = $this->account($tenantId, $organizationUnitId, $income, '4100', 'Sales Revenue', NormalBalance::Credit);
        $cogs = $this->account($tenantId, $organizationUnitId, $expense, '5200', 'Cost of Goods Sold', NormalBalance::Debit);

        $this->profile($tenantId, $organizationUnitId, 'sales_invoice', ['receivable' => $receivable, 'revenue' => $revenue, 'tax_payable' => $taxPayable]);
        $this->profile($tenantId, $organizationUnitId, 'payment_received', ['cash' => $cash, 'bank' => $bank, 'receivable' => $receivable]);
        $this->profile($tenantId, $organizationUnitId, 'inventory_issue', ['cost_of_goods_sold' => $cogs, 'inventory' => $inventory]);

        $year = FinanceFiscalYear::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
        FinanceFiscalPeriod::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'fiscal_year_id' => $year->getKey(),
            'name' => 'June 2026',
            'period_number' => 6,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
        ]);

        return [
            'cash' => (int) $cash->getKey(),
            'bank' => (int) $bank->getKey(),
            'withholding_receivable' => (int) $withholdingReceivable->getKey(),
        ];
    }

    private function accountType(int $tenantId, string $code, NormalBalance $normalBalance, StatementType $statementType): FinanceAccountType
    {
        return FinanceAccountType::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => Str::headline($code),
            'normal_balance' => $normalBalance->value,
            'statement_type' => $statementType->value,
            'is_active' => true,
        ]);
    }

    private function account(int $tenantId, ?int $organizationUnitId, FinanceAccountType $type, string $code, string $name, NormalBalance $normalBalance, bool $cash = false, bool $bank = false, bool $tax = false): FinanceAccount
    {
        return app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
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
    private function profile(int $tenantId, ?int $organizationUnitId, string $code, array $rules): void
    {
        $profile = FinancePostingProfile::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => Str::headline($code),
            'is_active' => true,
        ]);
        foreach ($rules as $key => $account) {
            FinancePostingProfileRule::query()->create([
                'tenant_id' => $tenantId,
                'posting_profile_id' => $profile->getKey(),
                'line_key' => $key,
                'account_id' => $account->getKey(),
            ]);
        }
    }

    private function paymentMethod(int $tenantId, string $code, string $type, string $direction): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'method_type' => $type,
            'direction_allowed' => $direction,
            'requires_reference' => false,
            'requires_instrument_details' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function taxGroup(int $tenantId, int $withholdingAccountId): int
    {
        $vat = DB::table('taxes')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'VAT-'.Str::upper(Str::random(4)),
            'name' => 'VAT',
            'tax_type' => 'vat',
            'calculation_method' => 'percentage',
            'is_withholding' => false,
            'recoverable' => false,
            'payable' => true,
            'receivable' => false,
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
            'payable' => false,
            'receivable' => true,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tax_rates')->insert([
            ['tenant_id' => $tenantId, 'tax_id' => $vat, 'rate' => '10.000000', 'effective_from' => '2026-01-01', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenantId, 'tax_id' => $wht, 'rate' => '5.000000', 'effective_from' => '2026-01-01', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $group = DB::table('tax_groups')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'TG-'.Str::upper(Str::random(4)),
            'name' => 'Sales Tax',
            'is_default' => false,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tax_group_lines')->insert([
            ['tenant_id' => $tenantId, 'tax_group_id' => $group, 'tax_id' => $vat, 'sequence' => 1, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenantId, 'tax_group_id' => $group, 'tax_id' => $wht, 'sequence' => 2, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('tax_posting_profiles')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'tax_id' => $wht,
            'direction' => 'withholding',
            'account_id' => $withholdingAccountId,
            'posting_key' => null,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $group;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
