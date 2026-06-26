<?php

declare(strict_types=1);

namespace Modules\Sales\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Sales\DTOs\CreateSalesAllocationData;
use Modules\Sales\DTOs\CreateSalesDeliveryData;
use Modules\Sales\DTOs\CreateSalesInvoiceData;
use Modules\Sales\DTOs\CreateSalesOrderData;
use Modules\Sales\DTOs\CreateSalesQuotationData;
use Modules\Sales\DTOs\CreateSalesReturnData;
use Modules\Sales\DTOs\SalesAllocationLineData;
use Modules\Sales\DTOs\SalesCreditNoteData;
use Modules\Sales\DTOs\SalesDeliveryLineData;
use Modules\Sales\DTOs\SalesHeaderAdjustmentData;
use Modules\Sales\DTOs\SalesInvoiceSourceData;
use Modules\Sales\DTOs\SalesLineData;
use Modules\Sales\DTOs\SalesReturnLineData;
use Modules\Sales\Enums\SalesAdjustmentCalculationBase;
use Modules\Sales\Enums\SalesAdjustmentCalculationType;
use Modules\Sales\Enums\SalesAdjustmentEffect;
use Modules\Sales\Enums\SalesAdjustmentType;
use Modules\Sales\Enums\SalesCreditNoteStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Enums\SalesQuotationStatus;
use Modules\Sales\Enums\SalesReturnType;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesHeaderAdjustment;
use Modules\Sales\Models\SalesInvoiceLink;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Models\SalesStatusHistory;
use Modules\Sales\Services\SalesAllocationService;
use Modules\Sales\Services\SalesAuthorizationService;
use Modules\Sales\Services\SalesCreditNoteService;
use Modules\Sales\Services\SalesDeliveryService;
use Modules\Sales\Services\SalesInvoiceIntegrationService;
use Modules\Sales\Services\SalesOrderService;
use Modules\Sales\Services\SalesPaymentPreparationService;
use Modules\Sales\Services\SalesQuotationService;
use Modules\Sales\Services\SalesReturnService;
use Tests\TestCase;

final class SalesEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_decimal_totals_and_conversion_to_order(): void
    {
        $context = $this->context();
        $quotation = app(SalesQuotationService::class)->create(new CreateSalesQuotationData(
            tenantId: $context['tenant_id'],
            quotationDate: '2026-06-11',
            customerId: $context['customer_id'],
            lines: [new SalesLineData(
                itemId: $context['item_id'],
                quantity: '3.333333',
                unitPrice: '2.100000',
                uomId: $context['uom_id'],
                discountCalculationType: SalesAdjustmentCalculationType::Fixed,
                discountAmount: '0.000001',
                taxCalculationType: SalesAdjustmentCalculationType::Fixed,
                taxAmount: '0.000002',
                chargeCalculationType: SalesAdjustmentCalculationType::Fixed,
                chargeAmount: '0.000003',
            )],
            adjustments: [
                new SalesHeaderAdjustmentData(
                    'Service',
                    SalesAdjustmentType::ServiceCharge,
                    SalesAdjustmentEffect::Increase,
                    '0.000000',
                    SalesAdjustmentCalculationType::Percentage,
                    SalesAdjustmentCalculationBase::SubtotalAfterLineDiscount,
                    '5.000000',
                ),
            ],
        ));

        $this->assertSame('7.000003', (string) $quotation->lines->first()->line_total);
        $this->assertSame('0.349999', (string) $quotation->header_increase_total);
        $this->assertSame('7.350002', (string) $quotation->grand_total);

        app(SalesQuotationService::class)->accept($quotation);
        $order = app(SalesQuotationService::class)->convertToOrder($quotation->refresh(), '2026-06-11', $context['warehouse_id']);

        $this->assertSame(SalesQuotationStatus::Converted, $quotation->refresh()->status);
        $this->assertSame($quotation->getKey(), $order->quotation_id);
        $this->assertSame('7.350002', (string) $order->grand_total);
        $this->assertSame($quotation->lines->first()->getKey(), $order->lines->first()->quotation_line_id);
        $this->assertGreaterThanOrEqual(2, SalesStatusHistory::query()->count());
    }

    public function test_delivery_allocates_and_issues_stock_but_skips_service_items(): void
    {
        $context = $this->context();
        $serviceItemId = $this->createItem($context['tenant_id'], 'SVC-'.Str::upper(Str::random(4)), $context['uom_id'], 'service', false);
        $this->seedStock($context, '20.000000');
        $order = $this->createOrder($context, [
            new SalesLineData($context['item_id'], '5.000000', '10.000000', uomId: $context['uom_id']),
            new SalesLineData($serviceItemId, '1.000000', '25.000000', uomId: $context['uom_id']),
        ]);
        app(SalesOrderService::class)->approve($order);

        $delivery = app(SalesDeliveryService::class)->create(new CreateSalesDeliveryData(
            tenantId: $context['tenant_id'],
            deliveryDate: '2026-06-11',
            customerId: $context['customer_id'],
            warehouseId: $context['warehouse_id'],
            salesOrderId: (int) $order->getKey(),
            lines: [
                new SalesDeliveryLineData($context['item_id'], '5.000000', '10.000000', salesOrderLineId: (int) $order->lines[0]->getKey(), uomId: $context['uom_id']),
                new SalesDeliveryLineData($serviceItemId, '1.000000', '25.000000', salesOrderLineId: (int) $order->lines[1]->getKey(), uomId: $context['uom_id']),
            ],
        ));
        $posted = app(SalesDeliveryService::class)->post($delivery);

        $this->assertSame('posted', $posted->status->value);
        $this->assertSame(1, InventoryAllocation::query()->where('source_type', 'sales_delivery')->count());
        $this->assertSame(1, InventoryMovement::query()->where('source_type', 'sales_delivery')->count());
        $this->assertSame(SalesOrderStatus::Approved, $order->refresh()->status);
        $this->assertSame('75.000000', (string) $order->refresh()->delivered_total);
    }

    public function test_sales_allocation_reserves_releases_and_delivery_consumes_existing_stock(): void
    {
        $context = $this->context();
        $this->seedStock($context, '20.000000');

        $releaseOrder = $this->createOrder($context, [
            new SalesLineData($context['item_id'], '3.000000', '10.000000', uomId: $context['uom_id']),
        ]);
        app(SalesOrderService::class)->approve($releaseOrder);
        $releaseLine = $releaseOrder->refresh()->load('lines')->lines->first();
        $allocation = app(SalesAllocationService::class)->create(new CreateSalesAllocationData(
            tenantId: $context['tenant_id'],
            allocationDate: '2026-06-11',
            salesOrderId: (int) $releaseOrder->getKey(),
            warehouseId: $context['warehouse_id'],
            lines: [new SalesAllocationLineData((int) $releaseLine->getKey(), '3.000000')],
        ));

        $this->assertSame('3.000000', (string) $releaseLine->refresh()->allocated_quantity);
        $this->assertSame('sales_order', InventoryAllocation::query()->find($allocation->lines->first()->inventory_allocation_id)?->source_type);

        app(SalesAllocationService::class)->release($allocation);
        $this->assertSame('0.000000', (string) $releaseLine->refresh()->allocated_quantity);
        $this->assertSame('released', $allocation->refresh()->status->value);

        $deliveryOrder = $this->createOrder($context, [
            new SalesLineData($context['item_id'], '2.000000', '10.000000', uomId: $context['uom_id']),
        ]);
        app(SalesOrderService::class)->approve($deliveryOrder);
        $deliveryLine = $deliveryOrder->refresh()->load('lines')->lines->first();
        app(SalesAllocationService::class)->create(new CreateSalesAllocationData(
            tenantId: $context['tenant_id'],
            allocationDate: '2026-06-11',
            salesOrderId: (int) $deliveryOrder->getKey(),
            warehouseId: $context['warehouse_id'],
            lines: [new SalesAllocationLineData((int) $deliveryLine->getKey(), '2.000000')],
        ));

        $this->deliver($context, $deliveryOrder, '2.000000');

        $this->assertSame(0, InventoryAllocation::query()->where('source_type', 'sales_delivery')->count());
        $this->assertSame('2.000000', app(DecimalMath::class)->normalize((string) DB::table('sales_allocation_lines')->where('sales_order_line_id', $deliveryLine->getKey())->value('issued_quantity')));
        $this->assertSame('2.000000', (string) $deliveryLine->refresh()->delivered_quantity);
    }

    public function test_non_base_uom_sales_return_restores_base_cost_once(): void
    {
        $context = $this->context();
        $boxUomId = $this->createUom($context['tenant_id'], 'BOX-'.Str::upper(Str::random(4)), false);
        DB::table('item_units')->insert([
            'tenant_id' => $context['tenant_id'],
            'item_id' => $context['item_id'],
            'uom_id' => $boxUomId,
            'unit_role' => 'sales',
            'conversion_factor' => '12.000000',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('uom_conversions')->insert([
            'tenant_id' => $context['tenant_id'],
            'row_version' => 1,
            'from_uom_id' => $boxUomId,
            'to_uom_id' => $context['uom_id'],
            'conversion_factor' => '12.000000',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedStock($context, '24.000000');
        $order = $this->createOrder($context, [
            new SalesLineData($context['item_id'], '2.000000', '120.000000', uomId: $boxUomId),
        ]);
        app(SalesOrderService::class)->approve($order);

        $delivery = app(SalesDeliveryService::class)->create(new CreateSalesDeliveryData(
            tenantId: $context['tenant_id'],
            deliveryDate: '2026-06-11',
            customerId: $context['customer_id'],
            warehouseId: $context['warehouse_id'],
            salesOrderId: (int) $order->getKey(),
            lines: [new SalesDeliveryLineData(
                itemId: $context['item_id'],
                deliveredQuantity: '2.000000',
                unitPrice: '120.000000',
                salesOrderLineId: (int) $order->lines->first()->getKey(),
                uomId: $boxUomId,
            )],
        ));
        $delivery = app(SalesDeliveryService::class)->post($delivery)->refresh()->load('lines');
        $return = app(SalesReturnService::class)->create(new CreateSalesReturnData(
            tenantId: $context['tenant_id'],
            returnDate: '2026-06-11',
            customerId: $context['customer_id'],
            returnType: SalesReturnType::ReferencedCustomerReturn,
            warehouseId: $context['warehouse_id'],
            lines: [new SalesReturnLineData(
                returnedQuantity: '1.000000',
                sourceLineType: 'sales_delivery_line',
                sourceLineId: (int) $delivery->lines->first()->getKey(),
            )],
        ));
        app(SalesReturnService::class)->post($return);

        $movement = InventoryMovement::query()
            ->where('source_type', 'sales_return')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame($boxUomId, $movement->entered_uom_id);
        $this->assertSame('1.000000', (string) $movement->entered_quantity);
        $this->assertSame('12.000000', (string) $movement->conversion_factor);
        $this->assertSame('48.000000', (string) $movement->entered_unit_cost);
        $this->assertSame('12.000000', (string) $movement->quantity);
        $this->assertSame('4.000000', (string) $movement->unit_cost);
        $this->assertSame('48.000000', (string) $movement->total_cost);
    }

    public function test_many_deliveries_create_partial_and_followup_customer_invoices(): void
    {
        $context = $this->context();
        $this->seedStock($context, '30.000000');
        $order = $this->createOrder($context, [
            new SalesLineData(
                $context['item_id'],
                '10.000000',
                '10.000000',
                uomId: $context['uom_id'],
                discountAmount: '10.000000',
                taxAmount: '18.000000',
            ),
        ], [
            new SalesHeaderAdjustmentData('Freight', SalesAdjustmentType::Freight, SalesAdjustmentEffect::Increase, '5.000000'),
        ]);
        app(SalesOrderService::class)->approve($order);
        $first = $this->deliver($context, $order, '4.000000');
        $second = $this->deliver($context, $order, '6.000000');

        $firstLineId = (int) $first->lines->first()->getKey();
        $secondLineId = (int) $second->lines->first()->getKey();
        $invoice = app(SalesInvoiceIntegrationService::class)->createCustomerInvoice(new CreateSalesInvoiceData(
            tenantId: $context['tenant_id'],
            invoiceDate: '2026-06-11',
            customerId: $context['customer_id'],
            sources: [
                new SalesInvoiceSourceData('sales_delivery', (int) $first->getKey()),
                new SalesInvoiceSourceData('sales_delivery', (int) $second->getKey(), [$secondLineId => '2.000000']),
            ],
        ));

        $this->assertCount(2, SalesInvoiceLink::query()->where('invoice_id', $invoice->getKey())->get());
        $this->assertSame('67.799999', (string) $invoice->grand_total);
        $this->assertSame('2.000000', (string) $second->lines->first()->refresh()->invoiced_quantity);
        $this->assertSame('4.000000', (string) $second->lines->first()->refresh()->remaining_quantity);

        $followup = app(SalesInvoiceIntegrationService::class)->createCustomerInvoice(new CreateSalesInvoiceData(
            tenantId: $context['tenant_id'],
            invoiceDate: '2026-06-12',
            customerId: $context['customer_id'],
            sources: [new SalesInvoiceSourceData('sales_delivery', (int) $second->getKey())],
        ));

        $this->assertSame('45.200001', (string) $followup->grand_total);
        $this->assertSame(
            '113.000000',
            app(DecimalMath::class)->add(
                (string) $invoice->grand_total,
                (string) $followup->grand_total,
            ),
        );
        $this->assertSame('posted', $second->refresh()->status->value);
        $this->assertSame('4.000000', (string) $first->lines->first()->refresh()->invoiced_quantity);
        $this->assertSame($firstLineId, $invoice->lines->first()->source_line_id);
    }

    public function test_reversing_a_full_delivery_reopens_the_sales_order(): void
    {
        $context = $this->context();
        $this->seedStock($context, '5.000000');
        $order = $this->createOrder($context, [
            new SalesLineData($context['item_id'], '2.000000', '10.000000', uomId: $context['uom_id']),
        ]);
        app(SalesOrderService::class)->approve($order);
        $delivery = $this->deliver($context, $order, '2.000000');

        $this->assertSame(SalesOrderStatus::Approved, $order->refresh()->status);

        app(SalesDeliveryService::class)->reverse($delivery);

        $line = $order->refresh()->load('lines')->lines->first();
        $this->assertSame(SalesOrderStatus::Approved, $order->status);
        $this->assertSame('0.000000', (string) $line->delivered_quantity);
        $this->assertSame('2.000000', (string) $line->remaining_deliverable_quantity);
    }

    public function test_cancelled_sales_return_releases_reserved_adjustments(): void
    {
        $context = $this->context();
        $this->seedStock($context, '5.000000');
        $order = $this->createOrder($context, [
            new SalesLineData($context['item_id'], '2.000000', '10.000000', uomId: $context['uom_id']),
        ], [
            new SalesHeaderAdjustmentData('Freight', SalesAdjustmentType::Freight, SalesAdjustmentEffect::Increase, '10.000000'),
        ]);
        app(SalesOrderService::class)->approve($order);
        $delivery = $this->deliver($context, $order, '2.000000');
        $deliveryLine = $delivery->lines->first();
        $adjustment = SalesHeaderAdjustment::query()
            ->where('source_type', 'sales_delivery')
            ->where('source_id', $delivery->getKey())
            ->firstOrFail();

        $return = app(SalesReturnService::class)->create(new CreateSalesReturnData(
            tenantId: $context['tenant_id'],
            returnDate: '2026-06-11',
            customerId: $context['customer_id'],
            returnType: SalesReturnType::ReferencedCustomerReturn,
            warehouseId: $context['warehouse_id'],
            lines: [new SalesReturnLineData('1.000000', 'sales_delivery_line', (int) $deliveryLine->getKey())],
        ));

        $this->assertSame('5.000000', (string) $adjustment->refresh()->returned_amount);
        $this->assertSame('5.000000', (string) $adjustment->remaining_amount);

        app(SalesReturnService::class)->cancel($return);

        $this->assertSame('0.000000', (string) $adjustment->refresh()->returned_amount);
        $this->assertSame('10.000000', (string) $adjustment->remaining_amount);
    }

    public function test_stale_sales_return_draft_is_revalidated_when_posted(): void
    {
        $context = $this->context();
        $this->seedStock($context, '5.000000');
        $order = $this->createOrder($context, [
            new SalesLineData($context['item_id'], '2.000000', '10.000000', uomId: $context['uom_id']),
        ]);
        app(SalesOrderService::class)->approve($order);
        $delivery = $this->deliver($context, $order, '2.000000');
        $deliveryLineId = (int) $delivery->lines->first()->getKey();

        $first = app(SalesReturnService::class)->create(new CreateSalesReturnData(
            tenantId: $context['tenant_id'],
            returnDate: '2026-06-11',
            customerId: $context['customer_id'],
            returnType: SalesReturnType::ReferencedCustomerReturn,
            warehouseId: $context['warehouse_id'],
            lines: [new SalesReturnLineData('2.000000', 'sales_delivery_line', $deliveryLineId)],
        ));
        $stale = app(SalesReturnService::class)->create(new CreateSalesReturnData(
            tenantId: $context['tenant_id'],
            returnDate: '2026-06-11',
            customerId: $context['customer_id'],
            returnType: SalesReturnType::ReferencedCustomerReturn,
            warehouseId: $context['warehouse_id'],
            lines: [new SalesReturnLineData('2.000000', 'sales_delivery_line', $deliveryLineId)],
        ));

        app(SalesReturnService::class)->post($first);

        try {
            app(SalesReturnService::class)->post($stale);
            $this->fail('Expected stale return post validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Returned quantity cannot exceed source remaining quantity.', $exception->getMessage());
        }
    }

    public function test_same_sales_source_cannot_be_invoiced_twice(): void
    {
        $context = $this->context();
        $this->seedStock($context, '5.000000');
        $order = $this->createOrder($context, [
            new SalesLineData($context['item_id'], '2.000000', '10.000000', uomId: $context['uom_id']),
        ]);
        app(SalesOrderService::class)->approve($order);
        $delivery = $this->deliver($context, $order, '2.000000');

        app(SalesInvoiceIntegrationService::class)->createCustomerInvoice(new CreateSalesInvoiceData(
            tenantId: $context['tenant_id'],
            invoiceDate: '2026-06-11',
            customerId: $context['customer_id'],
            sources: [new SalesInvoiceSourceData('sales_delivery', (int) $delivery->getKey())],
        ));

        try {
            app(SalesInvoiceIntegrationService::class)->createCustomerInvoice(new CreateSalesInvoiceData(
                tenantId: $context['tenant_id'],
                invoiceDate: '2026-06-12',
                customerId: $context['customer_id'],
                sources: [new SalesInvoiceSourceData('sales_delivery', (int) $delivery->getKey())],
            ));
            $this->fail('Expected duplicate invoice prevention to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('No sales source quantities remain to invoice.', $exception->getMessage());
        }
    }

    public function test_cancelled_sales_invoice_restores_delivery_and_order_invoiceable_quantity(): void
    {
        $context = $this->context();
        $this->seedStock($context, '5.000000');
        $order = $this->createOrder($context, [
            new SalesLineData(
                $context['item_id'],
                '2.000000',
                '10.000000',
                uomId: $context['uom_id'],
            ),
        ]);
        app(SalesOrderService::class)->approve($order);
        $delivery = $this->deliver($context, $order, '2.000000');
        $invoice = app(SalesInvoiceIntegrationService::class)->createCustomerInvoice(
            new CreateSalesInvoiceData(
                tenantId: $context['tenant_id'],
                invoiceDate: '2026-06-11',
                customerId: $context['customer_id'],
                sources: [
                    new SalesInvoiceSourceData(
                        'sales_delivery',
                        (int) $delivery->getKey(),
                    ),
                ],
            ),
        );

        app(InvoiceStatusService::class)->transition($invoice, InvoiceStatus::Cancelled);

        $delivery = $delivery->refresh()->load('lines');
        $order = $order->refresh()->load('lines');
        $this->assertSame('posted', $delivery->status->value);
        $this->assertSame('0.000000', (string) $delivery->lines->first()->invoiced_quantity);
        $this->assertSame('2.000000', (string) $delivery->lines->first()->remaining_quantity);
        $this->assertSame(SalesOrderStatus::Approved, $order->status);
        $this->assertSame('0.000000', (string) $order->lines->first()->invoiced_quantity);
        $this->assertSame('2.000000', (string) $order->lines->first()->remaining_invoiceable_quantity);
        $this->assertSame(
            'cancelled',
            SalesInvoiceLink::query()->where('invoice_id', $invoice->getKey())->value('status'),
        );

        $replacement = app(SalesInvoiceIntegrationService::class)->createCustomerInvoice(
            new CreateSalesInvoiceData(
                tenantId: $context['tenant_id'],
                invoiceDate: '2026-06-12',
                customerId: $context['customer_id'],
                sources: [
                    new SalesInvoiceSourceData(
                        'sales_delivery',
                        (int) $delivery->getKey(),
                    ),
                ],
            ),
        );
        $this->assertSame('20.000000', (string) $replacement->grand_total);
    }

    public function test_return_scenarios_apply_only_the_owned_inventory_and_credit_effects(): void
    {
        $context = $this->context();
        $this->seedStock($context, '50.000000');
        $order = $this->createOrder($context, [new SalesLineData($context['item_id'], '10.000000', '10.000000', uomId: $context['uom_id'])]);
        app(SalesOrderService::class)->approve($order);
        $delivery = $this->deliver($context, $order, '10.000000');
        $sourceLineId = (int) $delivery->lines->first()->getKey();

        $referenced = app(SalesReturnService::class)->create(new CreateSalesReturnData(
            tenantId: $context['tenant_id'],
            returnDate: '2026-06-11',
            customerId: $context['customer_id'],
            returnType: SalesReturnType::ReferencedCustomerReturn,
            warehouseId: $context['warehouse_id'],
            lines: [new SalesReturnLineData('2.000000', 'sales_delivery_line', $sourceLineId)],
        ));
        $referencedResult = app(SalesReturnService::class)->post($referenced);
        $this->assertNotNull($referencedResult->creditNoteId);
        $this->assertSame(1, InventoryMovement::query()->where('source_type', 'sales_return')->count());

        $creditOnly = app(SalesReturnService::class)->create(new CreateSalesReturnData(
            tenantId: $context['tenant_id'],
            returnDate: '2026-06-11',
            customerId: $context['customer_id'],
            returnType: SalesReturnType::CreditNoteOnly,
            reason: 'Price allowance',
            lines: [new SalesReturnLineData('1.000000', itemId: $context['item_id'], uomId: $context['uom_id'], unitPrice: '5.000000')],
        ));
        app(SalesReturnService::class)->post($creditOnly);
        $this->assertSame(2, SalesCreditNote::query()->count());
        $this->assertSame(1, InventoryMovement::query()->where('source_type', 'sales_return')->count());

        $inventoryOnly = $this->createManualReturn($context, SalesReturnType::InventoryAdjustmentOnly);
        app(SalesReturnService::class)->approve($inventoryOnly);
        app(SalesReturnService::class)->post($inventoryOnly);
        $this->assertSame(2, SalesCreditNote::query()->count());
        $this->assertSame(2, InventoryMovement::query()->where('source_type', 'sales_return')->count());

        $replacement = $this->createOrder($context, [new SalesLineData($context['item_id'], '1.000000', '0.000000', uomId: $context['uom_id'])]);
        app(SalesOrderService::class)->approve($replacement);
        $warranty = $this->createManualReturn($context, SalesReturnType::WarrantyReplacement, $replacement->getKey());
        app(SalesReturnService::class)->approve($warranty);
        $warrantyResult = app(SalesReturnService::class)->post($warranty);
        $this->assertSame(2, SalesCreditNote::query()->count());
        $this->assertCount(2, $warrantyResult->inventoryMovementIds);
        $this->assertSame(SalesOrderStatus::Approved, $replacement->refresh()->status);

        $exchangeItemId = $this->createItem($context['tenant_id'], 'EXCHANGE-'.Str::upper(Str::random(4)), $context['uom_id']);
        $this->seedStock($context, '5.000000', $exchangeItemId);
        $exchangeOrder = $this->createOrder($context, [new SalesLineData($exchangeItemId, '1.000000', '12.000000', uomId: $context['uom_id'])]);
        app(SalesOrderService::class)->approve($exchangeOrder);
        $exchange = $this->createManualReturn($context, SalesReturnType::ExchangeReturn, $exchangeOrder->getKey());
        app(SalesReturnService::class)->approve($exchange);
        $exchangeResult = app(SalesReturnService::class)->post($exchange);
        $this->assertSame(3, SalesCreditNote::query()->count());
        $this->assertCount(2, $exchangeResult->inventoryMovementIds);
        $this->assertSame(SalesOrderStatus::Approved, $exchangeOrder->refresh()->status);

        $damaged = app(SalesReturnService::class)->create(new CreateSalesReturnData(
            tenantId: $context['tenant_id'],
            returnDate: '2026-06-11',
            customerId: $context['customer_id'],
            returnType: SalesReturnType::ManualCustomerReturn,
            warehouseId: $context['warehouse_id'],
            warehouseLocationId: $context['location_id'],
            reason: 'Damaged imported return',
            approvalRequired: true,
            costBasis: '4.000000',
            lines: [new SalesReturnLineData('1.000000', itemId: $context['item_id'], uomId: $context['uom_id'], costBasis: '4.000000', conditionStatus: 'quarantine')],
        ));
        app(SalesReturnService::class)->approve($damaged);
        app(SalesReturnService::class)->post($damaged);
        $this->assertSame('quarantine', $damaged->lines->first()->condition_status);

        $opening = $this->createManualReturn($context, SalesReturnType::OpeningImportedReturn);
        app(SalesReturnService::class)->approve($opening);
        app(SalesReturnService::class)->post($opening);
        $this->assertSame(7, SalesReturn::query()->count());
    }

    public function test_payment_preparation_and_tenant_isolation(): void
    {
        $context = $this->context();
        $payment = app(SalesPaymentPreparationService::class)->prepareCustomerReceipt(
            $context['tenant_id'],
            '2026-06-11',
            '25.000000',
            customerId: $context['customer_id'],
        );
        $this->assertSame(PaymentType::CustomerReceipt, $payment->paymentType);
        $this->assertSame(PaymentDirection::Inbound, $payment->direction);
        $this->assertSame(0, Payment::query()->count());

        $other = $this->context('OTHER');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sales reference belongs to a different tenant.');
        app(SalesOrderService::class)->create(new CreateSalesOrderData(
            tenantId: $context['tenant_id'],
            salesOrderDate: '2026-06-11',
            customerId: $other['customer_id'],
            warehouseId: $context['warehouse_id'],
            lines: [new SalesLineData($context['item_id'], '1.000000', '1.000000', uomId: $context['uom_id'])],
        ));
    }

    public function test_sales_authorization_is_tenant_scoped_and_action_specific(): void
    {
        $tenantId = $this->createTenant('AUTH'.Str::upper(Str::random(2)));
        $otherTenantId = $this->createTenant('AUTH'.Str::upper(Str::random(2)));
        $userId = (int) \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Sales',
            'last_name' => 'Auth',
            'email' => 'sales-auth-'.Str::lower(Str::random(6)).'@example.test',
            'password' => 'secret',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionId = $this->insertSalesPermission($tenantId, SalesAuthorizationService::ORDERS_VIEW);
        $roleId = $this->insertRole($tenantId, 'Sales Order Viewer');
        DB::table('role_permissions')->insert([
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $authorization = app(SalesAuthorizationService::class);

        $this->assertTrue($authorization->can($userId, $tenantId, SalesAuthorizationService::ORDERS_VIEW));
        $this->assertFalse($authorization->can($userId, $tenantId, SalesAuthorizationService::ORDERS_APPROVE));
        $this->assertFalse($authorization->can($userId, $otherTenantId, SalesAuthorizationService::ORDERS_VIEW));
    }

    public function test_sales_credit_note_allocation_updates_invoice_balance(): void
    {
        $context = $this->context();
        $invoice = app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $context['tenant_id'],
            invoiceType: InvoiceType::Sales,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-11',
            invoiceNumber: 'INV-CREDIT-ALLOCATION',
            partyType: 'customer',
            partyId: $context['customer_id'],
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Credit allocation invoice',
                    quantity: '1.000000',
                    unitPrice: '100.000000',
                ),
            ],
        ));
        $statuses = app(InvoiceStatusService::class);
        $invoice = $statuses->transition($invoice, InvoiceStatus::Approved);
        $invoice = $statuses->transition($invoice, InvoiceStatus::Posted);

        $creditNotes = app(SalesCreditNoteService::class);
        $note = $creditNotes->create(new SalesCreditNoteData(
            tenantId: $context['tenant_id'],
            creditNoteDate: '2026-06-11',
            customerId: $context['customer_id'],
            amount: '40.000000',
            reason: 'Customer allowance',
        ));
        $note->status = SalesCreditNoteStatus::Posted;
        $note->save();

        $allocated = $creditNotes->allocate($note, $invoice, '15.000000');

        $this->assertSame(SalesCreditNoteStatus::Posted, $allocated->status);
        $this->assertSame('25.000000', (string) $allocated->remaining_amount);
        $this->assertSame('85.000000', (string) $invoice->refresh()->balance_due);

        $allocated = $creditNotes->allocate($allocated, $invoice->refresh(), '25.000000');

        $this->assertSame(SalesCreditNoteStatus::Allocated, $allocated->status);
        $this->assertSame('0.000000', (string) $allocated->remaining_amount);
        $this->assertSame('60.000000', (string) $invoice->refresh()->balance_due);
    }

    public function test_sales_invoice_source_requires_exact_organization_scope(): void
    {
        $context = $this->context();
        $organizationUnitId = $this->createOrganizationUnit(
            $context['tenant_id'],
            'SALES-ORG-A',
        );
        $otherOrganizationUnitId = $this->createOrganizationUnit(
            $context['tenant_id'],
            'SALES-ORG-B',
        );
        $this->seedStock($context, '2.000000');
        $order = $this->createOrder($context, [
            new SalesLineData(
                $context['item_id'],
                '1.000000',
                '10.000000',
                uomId: $context['uom_id'],
            ),
        ]);
        app(SalesOrderService::class)->approve($order);
        $delivery = $this->deliver($context, $order, '1.000000');
        $delivery->organization_unit_id = $organizationUnitId;
        $delivery->save();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Sales invoice source belongs to a different organization unit.',
        );

        app(SalesInvoiceIntegrationService::class)->createCustomerInvoice(
            new CreateSalesInvoiceData(
                tenantId: $context['tenant_id'],
                invoiceDate: '2026-06-11',
                organizationUnitId: $otherOrganizationUnitId,
                customerId: $context['customer_id'],
                sources: [
                    new SalesInvoiceSourceData(
                        'sales_delivery',
                        (int) $delivery->getKey(),
                    ),
                ],
            ),
        );
    }

    public function test_over_quantities_and_missing_uom_conversion_are_rejected(): void
    {
        $context = $this->context();
        $this->seedStock($context, '5.000000');
        $order = $this->createOrder($context, [
            new SalesLineData($context['item_id'], '2.000000', '10.000000', uomId: $context['uom_id']),
        ]);
        app(SalesOrderService::class)->approve($order);

        try {
            $this->deliver($context, $order, '3.000000');
            $this->fail('Expected over-delivery validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Delivered quantity cannot exceed sales order remaining deliverable quantity.', $exception->getMessage());
        }

        $delivery = $this->deliver($context, $order, '2.000000');
        $deliveryLine = $delivery->lines->first();

        try {
            app(SalesInvoiceIntegrationService::class)->createCustomerInvoice(new CreateSalesInvoiceData(
                tenantId: $context['tenant_id'],
                invoiceDate: '2026-06-11',
                customerId: $context['customer_id'],
                sources: [new SalesInvoiceSourceData(
                    'sales_delivery',
                    (int) $delivery->getKey(),
                    [(int) $deliveryLine->getKey() => '3.000000'],
                )],
            ));
            $this->fail('Expected over-invoicing validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Sales invoice quantity cannot exceed delivery remaining quantity.', $exception->getMessage());
        }

        try {
            app(SalesReturnService::class)->create(new CreateSalesReturnData(
                tenantId: $context['tenant_id'],
                returnDate: '2026-06-11',
                customerId: $context['customer_id'],
                returnType: SalesReturnType::ReferencedCustomerReturn,
                warehouseId: $context['warehouse_id'],
                lines: [new SalesReturnLineData('3.000000', 'sales_delivery_line', (int) $deliveryLine->getKey())],
            ));
            $this->fail('Expected over-return validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Returned quantity cannot exceed source remaining quantity.', $exception->getMessage());
        }

        $unsupportedUomId = $this->createUom($context['tenant_id'], 'BOX-'.Str::upper(Str::random(4)), false);
        try {
            $this->createOrder($context, [
                new SalesLineData($context['item_id'], '1.000000', '10.000000', uomId: $unsupportedUomId),
            ]);
            $this->fail('Expected missing UOM conversion validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Sales UOM conversion is required but no conversion exists.', $exception->getMessage());
        }
    }

    private function createOrder(array $context, array $lines, array $adjustments = []): SalesOrder
    {
        return app(SalesOrderService::class)->create(new CreateSalesOrderData(
            tenantId: $context['tenant_id'],
            salesOrderDate: '2026-06-11',
            customerId: $context['customer_id'],
            warehouseId: $context['warehouse_id'],
            lines: $lines,
            adjustments: $adjustments,
        ));
    }

    private function deliver(array $context, SalesOrder $order, string $quantity): SalesDelivery
    {
        $line = $order->refresh()->load('lines')->lines->first();
        $delivery = app(SalesDeliveryService::class)->create(new CreateSalesDeliveryData(
            tenantId: $context['tenant_id'],
            deliveryDate: '2026-06-11',
            customerId: $context['customer_id'],
            warehouseId: $context['warehouse_id'],
            salesOrderId: (int) $order->getKey(),
            lines: [new SalesDeliveryLineData(
                itemId: (int) $line->item_id,
                deliveredQuantity: $quantity,
                unitPrice: (string) $line->unit_price,
                salesOrderLineId: (int) $line->getKey(),
                uomId: $context['uom_id'],
            )],
        ));

        return app(SalesDeliveryService::class)->post($delivery)->refresh()->load(['lines', 'adjustments']);
    }

    private function createManualReturn(array $context, SalesReturnType $type, ?int $replacementOrderId = null): SalesReturn
    {
        return app(SalesReturnService::class)->create(new CreateSalesReturnData(
            tenantId: $context['tenant_id'],
            returnDate: '2026-06-11',
            customerId: $context['customer_id'],
            returnType: $type,
            warehouseId: $context['warehouse_id'],
            reason: 'Legacy customer return',
            replacementSalesOrderId: $replacementOrderId,
            approvalRequired: true,
            costBasis: '4.000000',
            lines: [new SalesReturnLineData(
                returnedQuantity: '1.000000',
                itemId: $context['item_id'],
                uomId: $context['uom_id'],
                costBasis: '4.000000',
            )],
        ));
    }

    private function seedStock(array $context, string $quantity, ?int $itemId = null): void
    {
        $adjustment = app(StockAdjustmentService::class)->create(new StockAdjustmentData(
            tenantId: $context['tenant_id'],
            adjustmentDate: '2026-06-11',
            adjustmentType: AdjustmentType::OpeningBalance,
            warehouseId: $context['warehouse_id'],
            reason: 'Sales test opening stock',
            lines: [new StockAdjustmentLineData(
                $itemId ?? $context['item_id'],
                '0.000000',
                $quantity,
                $quantity,
                '4.000000',
            )],
        ));
        app(StockAdjustmentService::class)->post($adjustment);
    }

    private function context(string $suffix = ''): array
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));
        $tenantId = $this->createTenant($suffix);
        $uomId = $this->createUom($tenantId, 'PCS-'.$suffix);
        $warehouseId = $this->createWarehouse($tenantId, 'WH-'.$suffix);

        return [
            'tenant_id' => $tenantId,
            'uom_id' => $uomId,
            'warehouse_id' => $warehouseId,
            'location_id' => $this->createWarehouseLocation($tenantId, $warehouseId, 'QUAR-'.$suffix),
            'customer_id' => $this->createCustomer($tenantId, 'CUS-'.$suffix),
            'item_id' => $this->createItem($tenantId, 'ITEM-'.$suffix, $uomId),
        ];
    }

    private function createTenant(string $suffix): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-SAL-'.$suffix,
            'name' => 'Sales Tenant '.$suffix,
            'slug' => 'sales-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function createOrganizationUnit(int $tenantId, string $name): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => $name,
            'code' => $name,
            'depth' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUom(int $tenantId, string $code, bool $isBase = true): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'code' => $code,
            'name' => 'Unit '.$code,
            'symbol' => 'pcs',
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => $isBase,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWarehouse(int $tenantId, string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Warehouse '.$code,
            'code' => $code,
            'type' => 'standard',
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWarehouseLocation(int $tenantId, int $warehouseId, string $code): int
    {
        return (int) DB::table('warehouse_locations')->insertGetId([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'row_version' => 1,
            'name' => $code,
            'code' => $code,
            'type' => 'bin',
            'is_active' => true,
            'is_pickable' => false,
            'is_receivable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCustomer(int $tenantId, string $code): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
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

    private function createItem(int $tenantId, string $code, int $uomId, string $type = 'stock', bool $stockable = true): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => 'Sales '.$code,
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

    private function insertSalesPermission(int $tenantId, string $name): int
    {
        return (int) DB::table('permissions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'guard_name' => 'auth-api',
            'module' => 'Sales',
            'description' => SalesAuthorizationService::descriptions()[$name] ?? $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertRole(int $tenantId, string $name): int
    {
        return (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'guard_name' => 'auth-api',
            'description' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
