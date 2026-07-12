<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Audit\Models\AuditLog;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;
use Modules\Finance\Services\AccountRoleAssignmentService;
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
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\FastPurchaseService;
use Modules\Purchase\Services\PurchaseAdjustmentAllocationLedger;
use Modules\Purchase\Services\PurchasePricingService;
use Modules\Supplier\Models\Supplier;
use Tests\Support\FinancePostingFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class FastPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_grn_only_receipt_creates_inventory_and_finance_without_invoice_or_payment(): void
    {
        $context = $this->context();

        $result = $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-GRN-ONLY',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => false, 'record_payment_now' => false],
        ]));

        $this->assertNotNull($result['documents']['purchase_order']);
        $this->assertNotNull($result['documents']['goods_receipt']);
        $this->assertNull($result['documents']['supplier_invoice']);
        $this->assertNull($result['documents']['supplier_payment']);
        $this->withinTenant($context, function (): void {
            $this->assertSame(1, PurchaseOrder::query()->count());
            $this->assertSame('approved', PurchaseOrder::query()->firstOrFail()->status->value);
            $this->assertSame(1, GoodsReceiptNote::query()->count());
            $this->assertSame(
                PurchaseOrder::query()->firstOrFail()->getKey(),
                GoodsReceiptNote::query()->firstOrFail()->purchase_order_id,
            );
            $this->assertNotNull(DB::table('goods_receipt_note_lines')->value('purchase_order_line_id'));
            $this->assertSame(1, InventoryMovement::query()->count());
            $this->assertSame('5.000000', (string) InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
            $this->assertSame(1, FinanceJournalEntry::query()->count());
            $this->assertSame(1, AuditLog::query()->where('event_name', 'purchase.fast_purchase.completed')->count());
        });
    }

    public function test_credit_purchase_creates_grn_invoice_relationship_and_balance(): void
    {
        $context = $this->context();

        $result = $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-CREDIT',
        ]));

        $this->assertNotNull($result['documents']['purchase_order']);
        $this->assertNotNull($result['documents']['goods_receipt']);
        $this->assertNotNull($result['documents']['supplier_invoice']);
        $this->assertSame('500.000000', $result['summary']['grand_total']);
        $this->withinTenant($context, function (): void {
            $this->assertSame('2026-07-16', (string) Invoice::query()->firstOrFail()->due_date->toDateString());
            $invoice = Invoice::query()->firstOrFail();
            $this->assertSame('500.000000', (string) $invoice->balance_due);
            $this->assertSame(1, PurchaseInvoiceLink::query()->where('invoice_id', $invoice->getKey())->count());
            $this->assertSame(1, FinanceJournalEntry::query()->count());
        });
    }

    public function test_invalid_payment_terms_are_rejected(): void
    {
        $context = $this->context();

        try {
            $this->previewFastPurchase($this->payload($context, [
                'payment_terms' => 'whenever_supplier_calls',
            ]));
            $this->fail('Expected invalid payment terms validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame('Unsupported payment terms selected.', $exception->errors()['payment_terms'][0] ?? null);
        }
    }

    public function test_explicit_due_date_payment_terms_are_persisted(): void
    {
        $context = $this->context();

        $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-EXPLICIT-DUE',
            'payment_terms' => 'explicit_due_date',
            'due_date' => '2026-06-29',
        ]));

        $this->withinTenant($context, function (): void {
            $this->assertSame('2026-06-29', Invoice::query()->firstOrFail()->due_date->toDateString());
        });
    }

    public function test_cash_purchase_allocates_payment_and_clears_invoice_balance(): void
    {
        $context = $this->context();

        $result = $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-CASH',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => true, 'record_payment_now' => true],
            'payment' => [
                'amount' => '500.000000',
                'payment_method_id' => $context['cash_method_id'],
                'reference' => 'PAY-FP-CASH',
            ],
        ]));

        $this->assertNotNull($result['documents']['supplier_payment']);
        $this->assertSame('500.000000', $result['summary']['paid_total']);
        $this->assertSame('0.000000', $result['summary']['balance_due']);
        $this->withinTenant($context, function (): void {
            $this->assertSame('0.000000', (string) Invoice::query()->firstOrFail()->balance_due);
            $this->assertSame(1, PaymentAllocation::query()->count());
            $this->assertSame('500.000000', (string) Payment::query()->firstOrFail()->allocated_amount);
            $this->assertSame(2, FinanceJournalEntry::query()->count());
        });
    }

    public function test_header_adjustments_affect_fast_purchase_invoice_and_payment_once(): void
    {
        $context = $this->context();

        $result = $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-ADJUSTED',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => true, 'record_payment_now' => true],
            'adjustments' => [
                [
                    'name' => 'Freight',
                    'adjustment_type' => 'freight',
                    'effect' => 'increase',
                    'amount' => '40.000000',
                    'allocation_method' => 'proportional',
                ],
                [
                    'name' => 'Order Discount',
                    'adjustment_type' => 'discount',
                    'effect' => 'decrease',
                    'amount' => '10.000000',
                    'allocation_method' => 'proportional',
                ],
            ],
            'payment' => [
                'lines' => [
                    ['amount' => '300.000000', 'payment_method_id' => $context['cash_method_id']],
                    $this->bankTransferPaymentLine($context, '230.000000', 'BT-FP-ADJUSTED'),
                ],
            ],
        ]));

        $this->assertSame('30.000000', $result['summary']['adjustment_total']);
        $this->assertSame('530.000000', $result['summary']['grand_total']);
        $this->assertSame('530.000000', $result['summary']['paid_total']);
        $this->assertSame('0.000000', $result['summary']['balance_due']);
        $this->withinTenant($context, function () use ($context): void {
            $this->assertSame('530.000000', (string) Invoice::query()->firstOrFail()->grand_total);
            $this->assertSame('530.000000', (string) Payment::query()->firstOrFail()->total_amount);
            $this->assertSame('530.000000', (string) PaymentAllocation::query()->firstOrFail()->allocated_amount);
            $movement = InventoryMovement::query()->firstOrFail();
            $this->assertSame('106.000000', (string) $movement->unit_cost);
            $inventoryDebit = (string) DB::table('finance_journal_lines')
                ->join('finance_journal_entries', 'finance_journal_entries.id', '=', 'finance_journal_lines.journal_entry_id')
                ->where('finance_journal_entries.source_type', 'goods_receipt_note')
                ->where('finance_journal_entries.source_id', GoodsReceiptNote::query()->firstOrFail()->getKey())
                ->sum('finance_journal_lines.debit');
            $this->assertSame(
                app(DecimalMath::class)->mul((string) $movement->quantity, (string) $movement->unit_cost),
                app(DecimalMath::class)->normalize($inventoryDebit),
            );
            $this->assertSame('530.000000', $this->accountDebit($context['inventory_account_id']));
            $this->assertSame('0.000000', $this->accountDebit($context['purchase_expense_account_id']));
            $this->assertSame('530.000000', $this->accountCredit($context['payable_account_id']));
            $this->assertSame('530.000000', $this->accountDebit($context['payable_account_id']));
            $this->assertSame('0.000000', $this->accountNetDebit($context['payable_account_id']));
            $this->assertSame('300.000000', $this->accountCredit($context['cash_account_id']));
            $this->assertSame('230.000000', $this->accountCredit($context['bank_account_id']));
            $this->assertSame('30.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->sum('signed_amount')));
            $this->assertSame('50.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->sum('recognized_at_grn_amount')));
            $this->assertSame('0.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->sum('recognized_at_invoice_amount')));
            $this->assertSame(2, DB::table('invoice_adjustments')->count());
            $this->assertSame(2, PaymentLine::query()->count());
            $this->assertSame(2, FinanceJournalEntry::query()->count());
        });
    }

    public function test_mixed_adjustment_residual_is_recognized_once_across_grn_and_invoice(): void
    {
        $context = $this->context();

        $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-MIXED-ADJ',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => true, 'record_payment_now' => true],
            'lines' => [
                ['item_id' => $context['stock_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '6.000000', 'unit_cost' => '10.000000', 'discount_amount' => '0.000000'],
                ['item_id' => $context['expense_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '4.000000', 'unit_cost' => '10.000000', 'discount_amount' => '0.000000'],
            ],
            'adjustments' => [[
                'name' => 'Freight',
                'adjustment_type' => 'freight',
                'effect' => 'increase',
                'amount' => '100.000000',
                'allocation_method' => 'proportional',
            ]],
            'payment' => [
                'amount' => '200.000000',
                'payment_method_id' => $context['cash_method_id'],
            ],
        ]));

        $this->withinTenant($context, function () use ($context): void {
            $this->assertSame('200.000000', (string) Invoice::query()->firstOrFail()->grand_total);
            $this->assertSame('0.000000', (string) Invoice::query()->firstOrFail()->balance_due);
            $this->assertSame('120.000000', $this->accountDebit($context['inventory_account_id']));
            $this->assertSame('80.000000', $this->accountDebit($context['purchase_expense_account_id']));
            $this->assertSame('200.000000', $this->accountCredit($context['payable_account_id']));
            $this->assertSame('200.000000', $this->accountDebit($context['payable_account_id']));
            $this->assertSame('0.000000', $this->accountNetDebit($context['payable_account_id']));
            $this->assertSame('100.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->sum('signed_amount')));
            $this->assertSame('60.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->sum('recognized_at_grn_amount')));
            $this->assertSame('40.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->sum('recognized_at_invoice_amount')));
            $this->assertSame(2, DB::table('invoice_adjustments')->count());
        });
    }

    public function test_manual_adjustment_allocations_are_validated_applied_and_persisted(): void
    {
        $context = $this->context();
        $secondStock = $this->item($context['tenant_id'], 'ITEM-STOCK-ALT', ItemType::Stock, true, $context['uom_id']);

        $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-MANUAL-ALLOC',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => true, 'record_payment_now' => true],
            'lines' => [
                ['client_line_key' => 'stock-a', 'item_id' => $context['stock_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '6.000000', 'unit_cost' => '10.000000', 'discount_amount' => '0.000000'],
                ['client_line_key' => 'stock-b', 'item_id' => (int) $secondStock->getKey(), 'uom_id' => $context['uom_id'], 'quantity' => '4.000000', 'unit_cost' => '10.000000', 'discount_amount' => '0.000000'],
            ],
            'adjustments' => [[
                'name' => 'Freight',
                'adjustment_type' => 'freight',
                'effect' => 'increase',
                'amount' => '100.000000',
                'allocation_method' => 'manual',
                'allocations' => [
                    ['client_line_key' => 'stock-a', 'amount' => '70.000000'],
                    ['client_line_key' => 'stock-b', 'amount' => '30.000000'],
                ],
            ]],
            'payment' => [
                'amount' => '200.000000',
                'payment_method_id' => $context['cash_method_id'],
            ],
        ]));

        $this->withinTenant($context, function () use ($context): void {
            $this->assertSame('200.000000', (string) Invoice::query()->firstOrFail()->grand_total);
            $this->assertSame('200.000000', $this->accountDebit($context['inventory_account_id']));
            $this->assertSame('0.000000', $this->accountNetDebit($context['payable_account_id']));
            $this->assertSame(2, DB::table('purchase_adjustment_allocations')->where('stage', 'manual_plan')->count());
            $this->assertSame('100.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->where('stage', 'grn_recognition')->sum('recognized_at_grn_amount')));
            $this->assertSame('0.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->where('stage', 'invoice_recognition')->sum('recognized_at_invoice_amount')));
        });
    }

    public function test_manual_adjustment_allocation_validation_returns_field_errors(): void
    {
        $context = $this->context();
        $base = [
            'adjustments' => [[
                'name' => 'Freight',
                'adjustment_type' => 'freight',
                'effect' => 'increase',
                'amount' => '100.000000',
                'allocation_method' => 'manual',
                'allocations' => [
                    ['client_line_key' => 'line-0', 'amount' => '99.000000'],
                ],
            ]],
        ];

        try {
            $this->previewFastPurchase($this->payload($context, $base));
            $this->fail('Expected manual allocation total validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame('Manual allocation total must equal the calculated adjustment amount.', $exception->errors()['adjustments.0.allocations'][0] ?? null);
        }

        try {
            $this->previewFastPurchase($this->payload($context, array_replace_recursive($base, [
                'adjustments' => [[
                    'allocations' => [
                        ['client_line_key' => 'line-0', 'amount' => '50.000000'],
                        ['client_line_key' => 'line-0', 'amount' => '50.000000'],
                    ],
                ]],
            ])));
            $this->fail('Expected duplicate manual allocation validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame('Manual allocation cannot reference the same purchase line more than once.', $exception->errors()['adjustments.0.allocations.1.client_line_key'][0] ?? null);
        }

        try {
            $this->previewFastPurchase($this->payload($context, array_replace_recursive($base, [
                'adjustments' => [[
                    'allocations' => [
                        ['client_line_key' => 'missing-line', 'amount' => '100.000000'],
                    ],
                ]],
            ])));
            $this->fail('Expected unknown manual allocation validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame('Manual allocation references an unknown purchase line.', $exception->errors()['adjustments.0.allocations.0.client_line_key'][0] ?? null);
        }
    }

    public function test_adjustment_ledger_rejects_invalid_metadata_and_creates_well_formed_reversals(): void
    {
        $context = $this->context();
        $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-LEDGER-GUARDS',
            'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => false, 'record_payment_now' => false],
            'adjustments' => [[
                'name' => 'Freight',
                'adjustment_type' => 'freight',
                'effect' => 'increase',
                'amount' => '25.000000',
                'allocation_method' => 'proportional',
            ]],
        ]));

        $this->withinTenant($context, function (): void {
            $allocation = DB::table('purchase_adjustment_allocations')
                ->where('stage', 'grn_recognition')
                ->where('entry_type', 'allocation')
                ->first();
            $this->assertNotNull($allocation);

            $ledger = app(PurchaseAdjustmentAllocationLedger::class);
            $invalidValues = [
                ['stage', 'invalid_stage', 'Purchase adjustment allocation stage is invalid.'],
                ['allocation_method', 'invalid_method', 'Purchase adjustment allocation method is invalid.'],
                ['allocated_amount', '-1.000000', 'Purchase adjustment allocation allocated_amount cannot be negative.'],
            ];

            foreach ($invalidValues as [$column, $value, $message]) {
                $original = $allocation->{$column};
                DB::table('purchase_adjustment_allocations')->where('id', $allocation->id)->update([$column => $value]);

                try {
                    $ledger->reverseForTarget((string) $allocation->target_type, (int) $allocation->target_id, 'test_reversal');
                    $this->fail("Expected {$column} validation to fail.");
                } catch (InvalidArgumentException $exception) {
                    $this->assertSame($message, $exception->getMessage());
                }

                DB::table('purchase_adjustment_allocations')->where('id', $allocation->id)->update([$column => $original]);
                $this->assertDatabaseMissing('purchase_adjustment_allocations', [
                    'reversal_of_id' => $allocation->id,
                    'entry_type' => 'reversal',
                ]);
            }

            $ledger->reverseForTarget((string) $allocation->target_type, (int) $allocation->target_id, 'test_reversal');

            $this->assertDatabaseHas('purchase_adjustment_allocations', [
                'reversal_of_id' => $allocation->id,
                'entry_type' => 'reversal',
                'stage' => 'grn_recognition',
                'allocation_method' => 'proportional',
                'event_type' => 'test_reversal',
            ]);
        });
    }

    public function test_first_and_last_invoice_adjustments_are_allocated_at_invoice_stage(): void
    {
        foreach (['first_invoice' => 'FP-FIRST-INVOICE', 'last_invoice' => 'FP-LAST-INVOICE'] as $method => $reference) {
            $context = $this->context();

            $this->createFastPurchase($this->payload($context, [
                'supplier_reference' => $reference,
                'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => true, 'record_payment_now' => true],
                'lines' => [
                    ['item_id' => $context['stock_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '6.000000', 'unit_cost' => '10.000000', 'discount_amount' => '0.000000'],
                    ['item_id' => $context['expense_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '4.000000', 'unit_cost' => '10.000000', 'discount_amount' => '0.000000'],
                ],
                'adjustments' => [[
                    'name' => 'Freight',
                    'adjustment_type' => 'freight',
                    'effect' => 'increase',
                    'amount' => '100.000000',
                    'allocation_method' => $method,
                ]],
                'payment' => [
                    'amount' => '200.000000',
                    'payment_method_id' => $context['cash_method_id'],
                ],
            ]));

            $this->withinTenant($context, function () use ($context): void {
                $this->assertSame('0.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->where('tenant_id', $context['tenant_id'])->where('stage', 'grn_recognition')->sum('recognized_at_grn_amount')));
                $this->assertSame('100.000000', app(DecimalMath::class)->normalize((string) DB::table('purchase_adjustment_allocations')->where('tenant_id', $context['tenant_id'])->where('stage', 'invoice_recognition')->sum('recognized_at_invoice_amount')));
                $this->assertSame('0.000000', (string) Invoice::query()->latest('id')->firstOrFail()->balance_due);
                $this->assertSame('0.000000', $this->accountNetDebit($context['payable_account_id']));
            });
        }
    }

    public function test_direct_non_stock_purchase_has_invoice_payment_and_no_inventory(): void
    {
        $context = $this->context();

        $result = $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-DIRECT',
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
            ],
        ]));

        $this->assertNotNull($result['documents']['purchase_order']);
        $this->assertNull($result['documents']['goods_receipt']);
        $this->assertNotNull($result['documents']['supplier_invoice']);
        $this->assertNotNull($result['documents']['supplier_payment']);
        $this->withinTenant($context, function (): void {
            $this->assertSame(0, InventoryMovement::query()->count());
            $this->assertSame(1, PurchaseOrder::query()->count());
            $this->assertSame(1, Invoice::query()->count());
            $this->assertSame(1, Payment::query()->count());
        });
    }

    public function test_receive_only_rejects_invoice_only_adjustments(): void
    {
        $context = $this->context();

        try {
            $this->previewFastPurchase($this->payload($context, [
                'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => false, 'record_payment_now' => false],
                'adjustments' => [[
                    'name' => 'Service Charge',
                    'adjustment_type' => 'service_charge',
                    'effect' => 'increase',
                    'amount' => '25.000000',
                ]],
            ]));
            $this->fail('Expected receive-only adjustment validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This adjustment requires a supplier invoice and cannot be used in receive-only Fast Purchase.',
                $exception->errors()['adjustments.0.adjustment_type'][0] ?? null,
            );
        }
    }

    public function test_receive_only_rejects_header_adjustments_when_non_stock_residual_would_remain(): void
    {
        $context = $this->context();

        try {
            $this->previewFastPurchase($this->payload($context, [
                'options' => ['receive_stock_now' => true, 'create_supplier_invoice_now' => false, 'record_payment_now' => false],
                'lines' => [
                    ['item_id' => $context['stock_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '3.000000', 'unit_cost' => '100.000000', 'discount_amount' => '0.000000'],
                    ['item_id' => $context['expense_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '1.000000', 'unit_cost' => '40.000000', 'discount_amount' => '0.000000'],
                ],
                'adjustments' => [[
                    'name' => 'Freight',
                    'adjustment_type' => 'freight',
                    'effect' => 'increase',
                    'amount' => '20.000000',
                ]],
            ]));
            $this->fail('Expected receive-only mixed adjustment validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Receive-only Fast Purchase cannot include header adjustments when non-stock lines are present because no invoice exists to recognize the residual.',
                $exception->errors()['adjustments.0.adjustment_type'][0] ?? null,
            );
        }
    }

    public function test_mixed_lines_receive_only_stock_and_invoice_all_lines(): void
    {
        $context = $this->context();

        $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-MIXED',
            'lines' => [
                ['item_id' => $context['stock_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '3.000000', 'unit_cost' => '100.000000', 'discount_amount' => '0.000000'],
                ['item_id' => $context['expense_item_id'], 'uom_id' => $context['uom_id'], 'quantity' => '1.000000', 'unit_cost' => '40.000000', 'discount_amount' => '0.000000'],
            ],
        ]));

        $this->withinTenant($context, function (): void {
            $this->assertSame(1, GoodsReceiptNote::query()->firstOrFail()->lines()->count());
            $this->assertSame(1, InventoryMovement::query()->count());
            $this->assertSame(2, Invoice::query()->firstOrFail()->lines()->count());
            $this->assertSame(
                ['goods_receipt_note_line', 'purchase_order_line'],
                DB::table('invoice_source_lines')->orderBy('source_line_type')->pluck('source_line_type')->all(),
            );
        });
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

        $result = $this->createFastPurchase($this->payload($context, [
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
                    ['amount' => '50.000000', 'payment_method_id' => $context['cash_method_id']],
                    $this->bankTransferPaymentLine($context, '25.000000', 'BT-FP-UOM-TAX'),
                ],
            ],
        ]));

        $this->assertSame('20.000000', $result['summary']['tax_total']);
        $this->assertSame('10.000000', $result['summary']['withholding_total']);
        $this->assertSame('210.000000', $result['summary']['grand_total']);
        $this->assertSame('75.000000', $result['summary']['paid_total']);
        $this->assertSame('135.000000', $result['summary']['balance_due']);
        $this->withinTenant($context, function (): void {
            $this->assertSame('24.000000', (string) InventoryMovement::query()->firstOrFail()->quantity);
            $this->assertSame(2, PaymentLine::query()->count());
            $this->assertSame('135.000000', (string) InvoiceBalance::query()->firstOrFail()->remaining_amount);
        });
    }

    public function test_preview_recalculates_discount_and_charge_without_persisting_documents(): void
    {
        $context = $this->context();

        $result = $this->previewFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-PREVIEW-PRICING',
            'lines' => [[
                'client_line_key' => 'line-preview-1',
                'item_id' => $context['stock_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '2.000000',
                'unit_cost' => '100.000000',
                'discount_calculation_type' => 'percentage',
                'discount_rate' => '10.000000',
                'discount_amount' => '999.000000',
                'charge_calculation_type' => 'fixed',
                'charge_amount' => '5.000000',
            ]],
        ]));

        $this->assertSame('line-preview-1', $result['lines'][0]['client_line_key']);
        $this->assertSame('200.000000', $result['lines'][0]['line_subtotal']);
        $this->assertSame('20.000000', $result['lines'][0]['discount_amount']);
        $this->assertSame('5.000000', $result['lines'][0]['charge_amount']);
        $this->assertSame('185.000000', $result['summary']['grand_total']);
        $this->assertSame(0, GoodsReceiptNote::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(0, AuditLog::query()->where('event_name', 'purchase.fast_purchase.completed')->count());
    }

    public function test_discount_and_charge_contract_matches_created_grn_and_invoice(): void
    {
        $context = $this->context();

        $result = $this->createFastPurchase($this->payload($context, [
            'supplier_reference' => 'FP-LINE-PRICING',
            'lines' => [[
                'client_line_key' => 'line-create-1',
                'item_id' => $context['stock_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '2.000000',
                'unit_cost' => '100.000000',
                'discount_calculation_type' => 'fixed',
                'discount_amount' => '10.000000',
                'charge_calculation_type' => 'percentage',
                'charge_rate' => '10.000000',
            ]],
        ]));

        $this->assertSame('10.000000', $result['summary']['discount_total']);
        $this->assertSame('20.000000', $result['summary']['charge_total']);
        $this->assertSame('210.000000', $result['summary']['grand_total']);
        $math = app(DecimalMath::class);
        $this->assertSame('20.000000', $math->normalize((string) DB::table('goods_receipt_note_lines')->value('charge_amount')));
        $this->assertSame('20.000000', $math->normalize((string) DB::table('invoice_lines')->value('charge_amount')));
    }

    public function test_item_variant_must_match_selected_fast_purchase_item(): void
    {
        $context = $this->context();
        $otherItem = $this->item($context['tenant_id'], 'ITEM-VAR-OTHER', ItemType::Stock, true, $context['uom_id']);
        $otherVariantId = $this->itemVariant($context['tenant_id'], (int) $otherItem->getKey(), 'VAR-OTHER');

        try {
            $this->previewFastPurchase($this->payload($context, [
                'lines' => [[
                    'item_id' => $context['stock_item_id'],
                    'item_variant_id' => $otherVariantId,
                    'uom_id' => $context['uom_id'],
                    'quantity' => '1.000000',
                    'unit_cost' => '100.000000',
                ]],
            ]));
            $this->fail('Expected item variant validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The selected item variant does not belong to the selected item.',
                $exception->errors()['lines.0.item_variant_id'][0] ?? null,
            );
        }
    }

    public function test_supplier_reference_makes_submission_idempotent_and_rejects_different_payloads(): void
    {
        $context = $this->context();
        $payload = $this->payload($context, ['supplier_reference' => 'FP-IDEMPOTENT']);
        $first = $this->createFastPurchase($payload);
        $second = $this->createFastPurchase($payload);

        $this->assertSame($first['documents']['goods_receipt']['id'], $second['documents']['goods_receipt']['id']);
        $this->withinTenant($context, function (): void {
            $this->assertSame(1, GoodsReceiptNote::query()->count());
            $this->assertSame(1, Invoice::query()->count());
        });

        $this->expectException(InvalidArgumentException::class);
        $this->createFastPurchase($this->payload($context, [
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

    public function test_client_line_key_does_not_change_fast_purchase_idempotency_hash(): void
    {
        $context = $this->context();
        $payload = $this->payload($context, [
            'supplier_reference' => 'FP-CLIENT-KEY-IDEMPOTENT',
            'lines' => [[
                'client_line_key' => 'first-client-key',
                'item_id' => $context['stock_item_id'],
                'uom_id' => $context['uom_id'],
                'quantity' => '5.000000',
                'unit_cost' => '100.000000',
                'discount_amount' => '0.000000',
            ]],
        ]);

        $first = $this->createFastPurchase($payload);
        $payload['lines'][0]['client_line_key'] = 'second-client-key';
        $second = $this->createFastPurchase($payload);

        $this->assertSame($first['documents']['goods_receipt']['id'], $second['documents']['goods_receipt']['id']);
        $this->withinTenant($context, function (): void {
            $this->assertSame(1, GoodsReceiptNote::query()->count());
            $this->assertSame(1, Invoice::query()->count());
        });
    }

    public function test_idempotency_hash_normalizes_decimal_strings(): void
    {
        $context = $this->context();
        $payload = $this->payload($context, [
            'supplier_reference' => 'FP-DECIMAL-IDEMPOTENT',
        ]);
        $payload['lines'][0]['quantity'] = '5';
        $payload['lines'][0]['unit_cost'] = '100.0';
        $payload['lines'][0]['discount_amount'] = '0';

        $first = $this->createFastPurchase($payload);

        $secondPayload = $this->payload($context, [
            'supplier_reference' => 'FP-DECIMAL-IDEMPOTENT',
        ]);
        $second = $this->createFastPurchase($secondPayload);

        $this->assertSame($first['documents']['purchase_order']['id'], $second['documents']['purchase_order']['id']);
        $this->assertSame(1, DB::table('idempotency_records')->count());
        $this->assertNotNull(DB::table('idempotency_records')->value('document_ids'));
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
            $this->createFastPurchase($this->payload($context, ['supplier_reference' => 'FP-ROLLBACK']));
            $this->fail('Expected posting failure.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Posting failed.', $exception->getMessage());
        }

        $this->assertSame(0, GoodsReceiptNote::query()->count());
        $this->assertSame(0, InventoryMovement::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(0, AuditLog::query()->where('event_name', 'purchase.fast_purchase.completed')->count());
    }

    public function test_scope_and_client_authority_validation(): void
    {
        $context = $this->context();
        $otherTenantId = $this->tenant('OTHER');
        $otherSupplierId = $this->supplier($otherTenantId, 'SUP-OTHER');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fast purchase totals, statuses, quantities, and finance accounts are server controlled.');
        $this->previewFastPurchase($this->payload($context, [
            'grand_total' => '1.000000',
        ]));
    }

    public function test_cross_tenant_fast_purchase_reference_returns_field_error(): void
    {
        $context = $this->context();
        $otherTenantId = $this->tenant('OTHER');
        $otherSupplierId = $this->supplier($otherTenantId, 'SUP-OTHER');

        try {
            $this->previewFastPurchase($this->payload($context, [
                'supplier_id' => $otherSupplierId,
            ]));
            $this->fail('Expected supplier validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame('The selected supplier is not available.', $exception->errors()['supplier_id'][0] ?? null);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $context, array $overrides = []): array
    {
        $payload = array_replace_recursive([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => null,
            'current_user_id' => $context['user_id'],
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

        foreach ($payload['lines'] as $index => &$line) {
            if (! is_array($line)) {
                continue;
            }

            $line['client_line_key'] ??= 'line-'.$index;
            $line['pricing_mode'] ??= 'manual';
            $line['manual_price_confirmed'] ??= true;
            $line['pricing_context_hash'] ??= $this->pricingContextHash($payload, $line);
        }
        unset($line);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $line
     */
    private function pricingContextHash(array $payload, array $line): string
    {
        $pricing = $this->withTenantExecutionContext(
            (int) $payload['tenant_id'],
            fn (): array => app(PurchasePricingService::class)->resolve(
                (int) $payload['tenant_id'],
                $payload['organization_unit_id'] === null ? null : (int) $payload['organization_unit_id'],
                Item::query()->findOrFail((int) $line['item_id']),
                (int) $payload['supplier_id'],
                isset($line['item_variant_id']) && $line['item_variant_id'] !== null ? (int) $line['item_variant_id'] : null,
                isset($payload['currency_id']) && $payload['currency_id'] !== null ? (int) $payload['currency_id'] : null,
                isset($line['uom_id']) && $line['uom_id'] !== null ? (int) $line['uom_id'] : null,
                (string) $payload['purchase_date'],
            ),
        );

        return (string) $pricing['pricing_context_hash'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function createFastPurchase(array $payload): array
    {
        return $this->withTenantRequestContext(
            (int) $payload['tenant_id'],
            (int) $payload['current_user_id'],
            fn (): array => app(FastPurchaseService::class)->create($payload),
            $payload['organization_unit_id'] === null ? null : (int) $payload['organization_unit_id'],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function previewFastPurchase(array $payload): array
    {
        return $this->withTenantRequestContext(
            (int) $payload['tenant_id'],
            (int) $payload['current_user_id'],
            fn (): array => app(FastPurchaseService::class)->preview($payload),
            $payload['organization_unit_id'] === null ? null : (int) $payload['organization_unit_id'],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function withinTenant(array $context, callable $callback): mixed
    {
        return $this->withTenantExecutionContext((int) $context['tenant_id'], $callback);
    }

    /**
     * @param  array<string, int>  $context
     * @return array<string, mixed>
     */
    private function bankTransferPaymentLine(array $context, string $amount, string $instrumentNumber): array
    {
        return [
            'amount' => $amount,
            'payment_method_id' => $context['bank_method_id'],
            'external_bank_name' => 'Fixture Bank',
            'instrument_number' => $instrumentNumber,
            'instrument_date' => '2026-06-16',
        ];
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
        $userId = $this->user($tenantId);

        return [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'uom_id' => $uomId,
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'stock_item_id' => (int) $stock->getKey(),
            'expense_item_id' => (int) $expense->getKey(),
            'cash_account_id' => $accounts['cash'],
            'bank_account_id' => $accounts['bank'],
            'inventory_account_id' => $accounts['inventory'],
            'tax_account_id' => $accounts['tax'],
            'payable_account_id' => $accounts['payable'],
            'purchase_expense_account_id' => $accounts['purchase_expense'],
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
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function user(int $tenantId): int
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'fast-purchase-'.Str::lower(Str::random(10)).'@example.test',
            ]),
        );
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
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Item => app(ItemCreationService::class)->create(new CreateItemData(
                tenantId: $tenantId,
                code: $code.'-'.Str::upper(Str::random(4)),
                name: 'Fast '.$code,
                itemType: $type,
                trackingType: TrackingType::None,
                costingMethod: $stockable ? CostingMethod::Fifo : CostingMethod::None,
                baseUomId: $uomId,
                isStockable: $stockable,
            )),
        );
    }

    private function itemVariant(int $tenantId, int $itemId, string $code): int
    {
        return (int) DB::table('item_variants')->insertGetId([
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => 'Variant '.$code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{cash: int, bank: int, inventory: int, tax: int, payable: int, purchase_expense: int}
     */
    private function finance(int $tenantId): array
    {
        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId): array {
            $asset = $this->accountType($tenantId, 'ASSET', NormalBalance::Debit, StatementType::BalanceSheet);
            $liability = $this->accountType($tenantId, 'LIABILITY', NormalBalance::Credit, StatementType::BalanceSheet);
            $expense = $this->accountType($tenantId, 'EXPENSE', NormalBalance::Debit, StatementType::IncomeStatement);
            $cash = $this->account($tenantId, $asset, '1010', 'Cash', NormalBalance::Debit, cash: true);
            $bank = $this->account($tenantId, $asset, '1020', 'Bank', NormalBalance::Debit, bank: true);
            $inventory = $this->account($tenantId, $asset, '1200', 'Inventory', NormalBalance::Debit);
            $tax = $this->account($tenantId, $asset, '1300', 'Input Tax', NormalBalance::Debit, tax: true);
            $payable = $this->account($tenantId, $liability, '2100', 'Payable', NormalBalance::Credit);
            $purchaseExpense = $this->account($tenantId, $expense, '5100', 'Purchase Expense', NormalBalance::Debit);

            $this->profile($tenantId, 'inventory_receipt', [
                'inventory' => $this->accountRole($tenantId, 'inventory', $inventory),
                'payable' => $this->accountRole($tenantId, 'inventory_payable', $payable),
            ]);
            $this->profile($tenantId, 'purchase_invoice', [
                'expense' => $this->accountRole($tenantId, 'purchase_expense', $purchaseExpense),
                'payable' => $this->accountRole($tenantId, 'purchase_payable', $payable),
                'tax_receivable' => $this->accountRole($tenantId, 'tax_receivable', $tax),
            ]);
            FinancePostingFixture::seedPurchaseWithholdingRole($tenantId);
            FinancePostingFixture::seedSupplierPaymentProfiles($tenantId);
            return [
                'cash' => (int) $cash->getKey(),
                'bank' => (int) $bank->getKey(),
                'inventory' => (int) $inventory->getKey(),
                'tax' => (int) $tax->getKey(),
                'payable' => (int) $payable->getKey(),
                'purchase_expense' => (int) $purchaseExpense->getKey(),
            ];
        });
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
     * @param  array<string, int>  $rules
     */
    private function profile(int $tenantId, string $code, array $rules): void
    {
        $profile = FinancePostingProfile::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'is_active' => true,
        ]);
        foreach ($rules as $key => $accountRoleId) {
            FinancePostingProfileRule::query()->create([
                'tenant_id' => $tenantId,
                'posting_profile_id' => $profile->getKey(),
                'line_key' => $key,
                'account_role_id' => $accountRoleId,
            ]);
        }
    }

    private function accountRole(int $tenantId, string $code, FinanceAccount $account): int
    {
        $service = app(AccountRoleAssignmentService::class);
        $role = $service->saveRole($tenantId, $code, Str::headline($code), null, true);
        $service->assign($tenantId, null, (int) $role->getKey(), (int) $account->getKey(), '2026-01-01');

        return (int) $role->getKey();
    }

    private function accountDebit(int $accountId): string
    {
        return app(DecimalMath::class)->normalize((string) DB::table('finance_journal_lines')
            ->where('account_id', $accountId)
            ->sum('debit'));
    }

    private function accountCredit(int $accountId): string
    {
        return app(DecimalMath::class)->normalize((string) DB::table('finance_journal_lines')
            ->where('account_id', $accountId)
            ->sum('credit'));
    }

    private function accountNetDebit(int $accountId): string
    {
        $math = app(DecimalMath::class);

        return $math->sub($this->accountDebit($accountId), $this->accountCredit($accountId));
    }

    private function paymentMethod(int $tenantId, string $code, string $type): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'method_type' => $type,
            'direction_allowed' => 'outbound',
            'requires_reference' => false,
            'requires_instrument_details' => $type === 'bank_transfer',
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
            ['tenant_id' => $tenantId, 'tax_id' => $vat, 'rate' => '10.000000', 'effective_from' => '2026-01-01', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenantId, 'tax_id' => $wht, 'rate' => '5.000000', 'effective_from' => '2026-01-01', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
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
            ['tenant_id' => $tenantId, 'tax_group_id' => $group, 'tax_id' => $vat, 'sequence' => 1, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenantId, 'tax_group_id' => $group, 'tax_id' => $wht, 'sequence' => 2, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        return (int) $group;
    }
}
