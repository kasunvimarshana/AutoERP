<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Concerns;

use InvalidArgumentException;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\Models\Payment;
use Modules\Sales\DTOs\CreateSalesDeliveryData;
use Modules\Sales\DTOs\CreateSalesInvoiceData;
use Modules\Sales\DTOs\CreateSalesOrderData;
use Modules\Sales\DTOs\SalesDeliveryLineData;
use Modules\Sales\DTOs\SalesHeaderAdjustmentData;
use Modules\Sales\DTOs\SalesInvoiceSourceData;
use Modules\Sales\DTOs\SalesLineData;
use Modules\Sales\Enums\SalesAdjustmentAllocationMethod;
use Modules\Sales\Enums\SalesAdjustmentCalculationBase;
use Modules\Sales\Enums\SalesAdjustmentCalculationType;
use Modules\Sales\Enums\SalesAdjustmentEffect;
use Modules\Sales\Enums\SalesAdjustmentType;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesOrder;

trait CreatesFastSalesDocuments
{
    /** @param array<string, mixed> $resolved */
    private function createDocuments(array $resolved): array
    {
        $order = null;
        $delivery = null;
        $invoice = null;
        $payment = null;
        $financePostings = [];
        if ((bool) $resolved['options']['create_sales_order_only']) {
            $order = $this->createSalesOrder($resolved);
        } else {
            if ((bool) $resolved['options']['deliver_items_now']) {
                $delivery = $this->createGoodsDelivery($resolved);
                $financePostings = array_merge($financePostings, $this->postInventoryFinance($resolved, $delivery));
            }
            if ((bool) $resolved['options']['create_customer_invoice_now']) {
                $invoice = $this->createCustomerInvoice($resolved, $delivery);
                $financePostings = array_merge($financePostings, $this->postInvoiceFinance($resolved, $invoice));
            }
            if ((bool) $resolved['options']['record_customer_receipt_now']) {
                if (! $invoice instanceof Invoice) {
                    throw new InvalidArgumentException('Customer receipt requires customer invoice creation.');
                }
                $payment = $this->createCustomerReceipt($resolved, $invoice);
            }
        }
        return [
            'sales_order' => $order,
            'goods_delivery' => $delivery,
            'customer_invoice' => $invoice,
            'customer_receipt' => $payment,
            'finance_postings' => $financePostings,
        ];
    }

    /** @param array<string, mixed> $resolved */
    private function createSalesOrder(array $resolved): SalesOrder
    {
        $lines = array_map(function (array $line): SalesLineData {
            return new SalesLineData(
                itemId: (int) $line['item_id'],
                quantity: (string) $line['quantity'],
                unitPrice: (string) $line['unit_price'],
                itemVariantId: $line['item_variant_id'],
                description: $line['description'],
                uomId: (int) $line['uom_id'],
                baseUomId: (int) $line['base_uom_id'],
                baseQuantity: (string) $line['base_quantity'],
                discountCalculationType: SalesAdjustmentCalculationType::Fixed,
                discountAmount: (string) $line['discount_amount'],
                taxCalculationType: SalesAdjustmentCalculationType::Fixed,
                taxAmount: (string) $line['non_withholding_tax_amount'],
            );
        }, $resolved['lines']);
        return $this->salesOrders->create(new CreateSalesOrderData(
            tenantId: (int) $resolved['tenant_id'],
            salesOrderDate: (string) $resolved['transaction_date'],
            customerId: (int) $resolved['customer']->getKey(),
            organizationUnitId: $resolved['organization_unit_id'],
            warehouseId: $resolved['warehouse_id'],
            warehouseLocationId: $resolved['warehouse_location_id'],
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            notes: $resolved['notes'],
            createdBy: $resolved['current_user_id'],
            lines: $lines,
            adjustments: $this->orderAdjustments($resolved),
        ))->load(['customer', 'warehouse', 'warehouseLocation', 'currency', 'lines.item', 'lines.orderedUom', 'adjustments']);
    }

    /** @param array<string, mixed> $resolved */
    private function createGoodsDelivery(array $resolved): SalesDelivery
    {
        $lines = [];
        foreach ($resolved['lines'] as $line) {
            if (! (bool) $line['is_stock']) {
                continue;
            }
            $lines[] = new SalesDeliveryLineData(
                itemId: (int) $line['item_id'],
                deliveredQuantity: (string) $line['quantity'],
                unitPrice: (string) $line['unit_price'],
                itemVariantId: $line['item_variant_id'],
                description: $line['description'],
                uomId: (int) $line['uom_id'],
                orderedQuantity: (string) $line['quantity'],
            );
        }
        $delivery = $this->deliveries->create(new CreateSalesDeliveryData(
            tenantId: (int) $resolved['tenant_id'],
            deliveryDate: (string) $resolved['transaction_date'],
            customerId: (int) $resolved['customer']->getKey(),
            warehouseId: (int) $resolved['warehouse_id'],
            organizationUnitId: $resolved['organization_unit_id'],
            warehouseLocationId: $resolved['warehouse_location_id'],
            notes: $resolved['notes'],
            deliveredBy: $resolved['current_user_id'],
            lines: $lines,
        ));
        return $this->deliveries->post($delivery, $resolved['current_user_id'])
            ->load(['lines.inventoryMovement', 'customer', 'warehouse', 'warehouseLocation']);
    }

    /** @param array<string, mixed> $resolved */
    private function createCustomerInvoice(array $resolved, ?SalesDelivery $delivery): Invoice
    {
        $directLines = [];
        $stockLines = array_values(array_filter($resolved['lines'], static fn (array $line): bool => (bool) $line['is_stock']));
        $deliveryLines = $delivery instanceof SalesDelivery ? $delivery->loadMissing('lines')->lines->values() : collect();
        if ($stockLines !== [] && $deliveryLines->count() !== count($stockLines)) {
            throw new InvalidArgumentException('Fast sales delivery lines could not be matched to invoice lines.');
        }
        foreach ($stockLines as $index => $line) {
            $deliveryLine = $deliveryLines->get($index);
            if (! $deliveryLine instanceof SalesDeliveryLine) {
                throw new InvalidArgumentException('Fast sales stock delivery line is missing.');
            }
            $directLines[] = $this->invoiceLine($line, true, 'sales_delivery_line', (int) $deliveryLine->getKey());
        }
        foreach ($resolved['lines'] as $line) {
            if (! (bool) $line['is_stock']) {
                $directLines[] = $this->invoiceLine($line, false);
            }
        }
        $sources = $delivery instanceof SalesDelivery
            ? [new SalesInvoiceSourceData('sales_delivery', (int) $delivery->getKey())]
            : [];
        return $this->salesInvoices->createCustomerInvoice(new CreateSalesInvoiceData(
            tenantId: (int) $resolved['tenant_id'],
            invoiceDate: (string) $resolved['transaction_date'],
            organizationUnitId: $resolved['organization_unit_id'],
            customerId: (int) $resolved['customer']->getKey(),
            dueDate: $resolved['due_date'],
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            notes: $resolved['notes'],
            createdBy: $resolved['current_user_id'],
            sources: $sources,
            status: InvoiceStatus::Posted,
            directLines: $directLines,
            adjustments: $this->invoiceAdjustments($resolved),
        ))->load(['lines', 'sources', 'sourceLines', 'adjustments', 'balance']);
    }

    /** @param array<string, mixed> $resolved */
    private function createCustomerReceipt(array $resolved, Invoice $invoice): Payment
    {
        $payment = $resolved['payment'];
        $amount = (string) $payment['amount'];
        $actorId = $resolved['current_user_id'];
        $data = $this->salesPayments->prepareCustomerReceipt(
            tenantId: (int) $resolved['tenant_id'],
            paymentDate: (string) $resolved['transaction_date'],
            amount: $amount,
            organizationUnitId: $resolved['organization_unit_id'],
            customerId: (int) $resolved['customer']->getKey(),
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            referenceNumber: $payment['reference'] ?? $resolved['customer_reference'],
            lines: $payment['lines'],
            allocations: [new PaymentAllocationData(
                invoiceId: (int) $invoice->getKey(),
                allocatedAmount: $amount,
                allocationDate: (string) $resolved['transaction_date'],
                allowOverpayment: false,
                metadata: ['fast_sales' => true, 'customer_reference' => $resolved['customer_reference']],
            )],
            createdBy: $actorId,
            notes: $resolved['notes'],
        );
        $receipt = $this->payments->create($data);
        $receipt = $this->paymentDocuments->submit($receipt, (int) $receipt->row_version, $actorId);
        $receipt = $this->paymentDocuments->approve($receipt, (int) $receipt->row_version, $actorId);
        return $this->paymentPostings
            ->post($receipt, (int) $receipt->row_version, $actorId)
            ->load(['lines', 'allocations', 'unappliedBalance', 'lifecycleEvents']);
    }

    /** @param array<string, mixed> $resolved @return list<PostingResultData> */
    private function postInventoryFinance(array $resolved, SalesDelivery $delivery): array
    {
        $delivery->loadMissing('lines.inventoryMovement');
        $movements = $delivery->lines->pluck('inventoryMovement')->filter()->values();
        $cost = $this->math->sum($movements->map(fn (InventoryMovement $movement): string => (string) $movement->total_cost)->all());
        if ($this->math->isZero($cost)) {
            return [];
        }
        return [$this->financePostings->post(new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'inventory_issue',
                sourceId: (int) $delivery->getKey(),
                tenantId: (int) $resolved['tenant_id'],
                organizationUnitId: $resolved['organization_unit_id'],
                sourceModule: 'sales',
                sourceNumber: (string) $delivery->delivery_number,
                sourceDate: $delivery->delivery_date?->toDateString() ?? (string) $resolved['transaction_date'],
            ),
            postingDate: (string) $resolved['transaction_date'],
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            lines: [
                new FinancePostingLine(null, 'Cost of goods sold', debit: $cost, profileKey: 'cost_of_goods_sold'),
                new FinancePostingLine(null, 'Inventory', credit: $cost, profileKey: 'inventory'),
            ],
            description: 'Fast sales inventory issue '.$delivery->delivery_number,
            postingProfileCode: 'inventory_issue',
        ), $resolved['current_user_id'])];
    }

    /** @param array<string, mixed> $resolved @return list<PostingResultData> */
    private function postInvoiceFinance(array $resolved, Invoice $invoice): array
    {
        $revenue = $resolved['summary']['revenue_total'];
        $tax = $resolved['summary']['tax_total'];
        $withholding = $resolved['summary']['withholding_total'];
        $grossReceivable = $this->math->add($revenue, $tax);
        if ($this->math->isZero($grossReceivable)) {
            return [];
        }
        $postings = [];
        $lines = [new FinancePostingLine(null, 'Customer receivable', debit: $grossReceivable, profileKey: 'receivable')];
        if (! $this->math->isZero($revenue)) {
            $lines[] = new FinancePostingLine(null, 'Sales revenue', credit: $revenue, profileKey: 'revenue');
        }
        if (! $this->math->isZero($tax)) {
            $lines[] = new FinancePostingLine(null, 'Output tax', credit: $tax, profileKey: 'tax_payable');
        }
        $postings[] = $this->financePostings->post(new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'sales_invoice',
                sourceId: (int) $invoice->getKey(),
                tenantId: (int) $invoice->tenant_id,
                organizationUnitId: $invoice->organization_unit_id,
                sourceModule: 'sales',
                sourceNumber: (string) $invoice->invoice_number,
                sourceDate: $invoice->invoice_date?->toDateString() ?? (string) $resolved['transaction_date'],
            ),
            postingDate: (string) $resolved['transaction_date'],
            currencyId: $invoice->currency_id,
            exchangeRate: (string) $invoice->exchange_rate,
            lines: $lines,
            description: 'Fast sales customer invoice '.$invoice->invoice_number,
            postingProfileCode: 'sales_invoice',
        ), $resolved['current_user_id']);
        if (! $this->math->isZero($withholding)) {
            $receivableAccount = $this->postingProfileAccount(
                (int) $invoice->tenant_id,
                $invoice->organization_unit_id === null ? null : (int) $invoice->organization_unit_id,
                'sales_invoice',
                'receivable',
            );
            $context = $this->taxDocuments->withholdingPostingContextForDocument(
                $this->invoiceTaxDocuments->map($invoice),
                (string) $resolved['transaction_date'],
                (string) $receivableAccount->code,
                (string) $receivableAccount->name,
            );
            if ($context->financeContext->lines !== []) {
                $postings[] = $this->financePostings->post($context->financeContext, $resolved['current_user_id']);
            }
        }
        return $postings;
    }

    /** @param array<string, mixed> $resolved @return list<SalesHeaderAdjustmentData> */
    private function orderAdjustments(array $resolved): array
    {
        return $this->math->isZero($resolved['summary']['withholding_total']) ? [] : [new SalesHeaderAdjustmentData(
            name: 'Withholding',
            adjustmentType: SalesAdjustmentType::Withholding,
            effect: SalesAdjustmentEffect::Decrease,
            amount: (string) $resolved['summary']['withholding_total'],
            calculationType: SalesAdjustmentCalculationType::Fixed,
            calculationBase: SalesAdjustmentCalculationBase::SubtotalAfterLineAdjustments,
            rate: '0.000000',
            allocationMethod: SalesAdjustmentAllocationMethod::Manual,
            isAllocatable: true,
            description: 'Fast sales withholding',
        )];
    }

    /** @param array<string, mixed> $resolved @return list<InvoiceAdjustmentData> */
    private function invoiceAdjustments(array $resolved): array
    {
        return $this->math->isZero($resolved['summary']['withholding_total']) ? [] : [new InvoiceAdjustmentData(
            name: 'Withholding',
            adjustmentType: AdjustmentType::Withholding,
            effect: AdjustmentEffect::Decrease,
            amount: (string) $resolved['summary']['withholding_total'],
            calculationType: 'fixed',
            rate: '0.000000',
            sourceAmount: (string) $resolved['summary']['withholding_total'],
            allocationMethod: AllocationMethod::Manual,
            isSystemGenerated: true,
            description: 'Fast sales withholding',
        )];
    }

    /** @param array<string, mixed> $line */
    private function invoiceLine(array $line, bool $isStock, ?string $sourceLineType = null, ?int $sourceLineId = null): InvoiceLineData
    {
        return new InvoiceLineData(
            lineNumber: (int) $line['line_number'],
            description: (string) $line['description'],
            quantity: (string) $line['quantity'],
            unitPrice: (string) $line['unit_price'],
            lineType: $line['line_type'],
            itemId: (int) $line['item_id'],
            uomId: (int) $line['uom_id'],
            discountAmount: (string) $line['discount_amount'],
            taxAmount: (string) $line['non_withholding_tax_amount'],
            sourceLineType: $sourceLineType,
            sourceLineId: $sourceLineId,
            metadata: [
                'fast_sales' => true,
                'customer_reference' => $line['customer_reference'] ?? null,
                'tax_group_id' => $line['tax_group_id'],
                'is_stock' => $isStock,
            ],
        );
    }

    private function postingProfileAccount(int $tenantId, ?int $organizationUnitId, string $profileCode, string $lineKey): FinanceAccount
    {
        $profile = FinancePostingProfile::query()
            ->with('rules.account')
            ->where('tenant_id', $tenantId)
            ->where('code', $profileCode)
            ->where('is_active', true)
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $organizationUnitId),
            )
            ->first();
        if (! $profile instanceof FinancePostingProfile) {
            throw new InvalidArgumentException("Posting profile [{$profileCode}] is missing or inactive for this scope.");
        }
        $account = $profile->rules->firstWhere('line_key', $lineKey)?->account;
        if (! $account instanceof FinanceAccount) {
            throw new InvalidArgumentException("Posting profile [{$profileCode}] is missing account mapping [{$lineKey}].");
        }
        return $account;
    }
}
