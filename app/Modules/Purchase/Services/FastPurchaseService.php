<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Audit\DTOs\AuditLogActivityData;
use Modules\Audit\Models\AuditLogModel;
use Modules\Audit\Services\AuditLogs\LogActivityService;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Models\FinanceAccount;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemPrice;
use Modules\Item\Models\ItemUnit;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\DTOs\PurchaseInvoiceSourceData;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Validators\PurchaseValidationService;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierItemMapping;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\Models\TaxGroup;
use Modules\Tax\Services\TaxCalculationService;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class FastPurchaseService
{
    private const SUPPLIER_TYPE = 'supplier';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseValidationService $validator,
        private readonly PurchaseUomService $uoms,
        private readonly TaxCalculationService $taxes,
        private readonly GoodsReceiptNoteService $goodsReceipts,
        private readonly PurchaseInvoiceIntegrationService $purchaseInvoices,
        private readonly PurchasePaymentIntegrationService $purchasePayments,
        private readonly PaymentCreationService $payments,
        private readonly FinancePostingInterface $financePostings,
        private readonly LogActivityService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function context(array $payload): array
    {
        $tenantId = (int) $payload['tenant_id'];
        $organizationUnitId = $this->nullableInt($payload['organization_unit_id'] ?? null);
        $search = trim((string) ($payload['search'] ?? ''));
        $perPage = max(1, min(100, (int) ($payload['per_page'] ?? 25)));

        return [
            'defaults' => [
                'purchase_date' => now()->toDateString(),
                'exchange_rate' => '1.000000',
            ],
            'endpoints' => [
                'supplier_search' => '/api/v1/suppliers/lookup/active',
                'item_search' => '/api/v1/items/lookup',
                'preview' => '/api/v1/purchase/fast-purchases/preview',
                'create' => '/api/v1/purchase/fast-purchases',
            ],
            'warehouses' => $this->warehouseOptions($tenantId, $organizationUnitId, $search, $perPage),
            'currencies' => $this->currencyOptions($search, $perPage),
            'payment_methods' => $this->paymentMethodOptions($tenantId, $organizationUnitId, $search, $perPage),
            'payment_accounts' => $this->paymentAccountOptions($tenantId, $organizationUnitId, $search, $perPage),
            'tax_groups' => $this->taxGroupOptions($tenantId, $organizationUnitId, $search, $perPage),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function preview(array $payload): array
    {
        $this->rejectClientAuthorityFields($payload);

        return $this->previewResponse($this->resolve($payload, lockRecords: false));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        $this->rejectClientAuthorityFields($payload);

        return DB::transaction(function () use ($payload): array {
            $resolved = $this->resolve($payload, lockRecords: true);
            $referenceHash = $this->referenceHash($resolved);
            $requestHash = $this->requestHash($payload);
            $existing = $this->completedReference($resolved, $referenceHash);

            if ($existing instanceof AuditLogModel) {
                $metadata = is_array($existing->metadata) ? $existing->metadata : [];
                if (($metadata['request_hash'] ?? null) !== $requestHash) {
                    throw new InvalidArgumentException('Supplier reference was already used for a different fast purchase.');
                }

                $values = is_array($existing->new_values) ? $existing->new_values : [];
                if (is_array($values['response'] ?? null)) {
                    return $values['response'];
                }
            }

            $documents = $this->createDocuments($resolved);
            $response = $this->createResponse($resolved, $documents);
            $this->writeAuditLog($resolved, $referenceHash, $requestHash, $response);

            return $response;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolve(array $payload, bool $lockRecords): array
    {
        $tenantId = (int) $payload['tenant_id'];
        $organizationUnitId = $this->nullableInt($payload['organization_unit_id'] ?? null);
        $purchaseDate = (string) $payload['purchase_date'];
        $supplier = $this->supplier($tenantId, $organizationUnitId, (int) $payload['supplier_id'], $lockRecords);
        $supplierReference = trim((string) ($payload['supplier_reference'] ?? ''));
        $currencyId = $this->currencyId($payload, $supplier, $tenantId, $organizationUnitId, $lockRecords);
        $exchangeRate = $this->math->normalize((string) ($payload['exchange_rate'] ?? '1.000000'));
        $warehouseId = $this->nullableInt($payload['warehouse_id'] ?? null);
        $warehouseLocationId = $this->nullableInt($payload['warehouse_location_id'] ?? null);
        $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
        $receiveStock = (bool) ($options['receive_stock_now'] ?? false);
        $createInvoice = (bool) ($options['create_supplier_invoice_now'] ?? false);
        $recordPayment = (bool) ($options['record_payment_now'] ?? false);

        $lines = $this->resolveLines(
            is_array($payload['lines'] ?? null) ? $payload['lines'] : [],
            $supplier,
            $tenantId,
            $organizationUnitId,
            $purchaseDate,
            $currencyId,
            $lockRecords,
        );
        $this->validateMode($lines, $receiveStock, $createInvoice, $recordPayment);

        if ($receiveStock) {
            if ($warehouseId === null) {
                throw new InvalidArgumentException('Warehouse is required when receiving stock.');
            }

            $this->warehouse($tenantId, $organizationUnitId, $warehouseId, $lockRecords);
            if ($warehouseLocationId !== null) {
                $this->warehouseLocation($tenantId, $organizationUnitId, $warehouseId, $warehouseLocationId, $lockRecords);
            }
        }

        $summary = $this->summary($lines);
        $payment = $this->resolvePayment($payload, $recordPayment, $summary, $tenantId, $organizationUnitId, $lockRecords);
        $this->validateCredit($supplier, $createInvoice, $summary['grand_total'], $payment['amount']);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'current_user_id' => $this->nullableInt($payload['current_user_id'] ?? null),
            'supplier' => $supplier,
            'supplier_reference' => $supplierReference,
            'purchase_date' => $purchaseDate,
            'due_date' => $this->dueDate($purchaseDate, (string) ($payload['payment_terms'] ?? ''), $payload['due_date'] ?? null),
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $warehouseLocationId,
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'payment_terms' => trim((string) ($payload['payment_terms'] ?? '')),
            'notes' => $this->notes($payload),
            'options' => [
                'receive_stock_now' => $receiveStock,
                'create_supplier_invoice_now' => $createInvoice,
                'record_payment_now' => $recordPayment,
            ],
            'mode' => $this->mode($lines, $receiveStock, $createInvoice, $recordPayment),
            'lines' => $lines,
            'summary' => $summary,
            'payment' => $payment,
        ];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function createDocuments(array $resolved): array
    {
        $goodsReceipt = null;
        $invoice = null;
        $payment = null;
        $financePostings = [];

        if ((bool) $resolved['options']['receive_stock_now']) {
            $goodsReceipt = $this->createGoodsReceipt($resolved);
            $financePostings = array_merge($financePostings, $this->postInventoryFinance($resolved, $goodsReceipt));
        }

        if ((bool) $resolved['options']['create_supplier_invoice_now']) {
            $invoice = $this->createSupplierInvoice($resolved, $goodsReceipt);
            $financePostings = array_merge($financePostings, $this->postInvoiceFinance($resolved, $invoice));
        }

        if ((bool) $resolved['options']['record_payment_now']) {
            if (! $invoice instanceof Invoice) {
                throw new InvalidArgumentException('Supplier payment requires a supplier invoice.');
            }

            $payment = $this->createSupplierPayment($resolved, $invoice);
            $financePostings = array_merge($financePostings, $this->postPaymentFinance($resolved, $payment));
        }

        return [
            'goods_receipt' => $goodsReceipt,
            'supplier_invoice' => $invoice,
            'supplier_payment' => $payment,
            'finance_postings' => $financePostings,
        ];
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function createGoodsReceipt(array $resolved): GoodsReceiptNote
    {
        $lines = [];
        foreach ($resolved['lines'] as $line) {
            if (! (bool) $line['is_stock']) {
                continue;
            }

            $lines[] = new GoodsReceiptNoteLineData(
                itemId: (int) $line['item_id'],
                receivedQuantity: $line['quantity'],
                acceptedQuantity: $line['quantity'],
                unitPrice: $line['unit_cost'],
                itemVariantId: $line['item_variant_id'],
                description: $line['description'],
                uomId: $line['uom_id'],
                orderedUomId: $line['uom_id'],
                baseUomId: $line['base_uom_id'],
                uomConversionFactor: $line['uom_conversion_factor'],
                baseReceivedQuantity: $line['base_quantity'],
                baseAcceptedQuantity: $line['base_quantity'],
                orderedQuantity: $line['quantity'],
                discountAmount: $line['discount_amount'],
                taxAmount: $line['non_withholding_tax_amount'],
            );
        }

        $grn = $this->goodsReceipts->create(new CreateGoodsReceiptNoteData(
            tenantId: (int) $resolved['tenant_id'],
            receivedDate: (string) $resolved['purchase_date'],
            warehouseId: (int) $resolved['warehouse_id'],
            organizationUnitId: $resolved['organization_unit_id'],
            warehouseLocationId: $resolved['warehouse_location_id'],
            supplierType: self::SUPPLIER_TYPE,
            supplierId: (int) $resolved['supplier']->getKey(),
            notes: $resolved['notes'],
            receivedBy: $resolved['current_user_id'],
            lines: $lines,
        ));

        return $this->goodsReceipts->post($grn, $resolved['current_user_id'])
            ->load(['lines.inventoryMovement', 'supplier', 'warehouse', 'warehouseLocation']);
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function createSupplierInvoice(array $resolved, ?GoodsReceiptNote $goodsReceipt): Invoice
    {
        $directLines = [];
        foreach ($resolved['lines'] as $line) {
            if ((bool) $line['is_stock']) {
                continue;
            }

            $directLines[] = new InvoiceLineData(
                lineNumber: count($directLines) + 1,
                description: $line['description'],
                quantity: $line['quantity'],
                unitPrice: $line['unit_cost'],
                lineType: InvoiceLineType::Item,
                itemId: (int) $line['item_id'],
                uomId: $line['uom_id'],
                discountAmount: $line['discount_amount'],
                taxAmount: $line['non_withholding_tax_amount'],
                metadata: [
                    'fast_purchase' => true,
                    'supplier_reference' => $resolved['supplier_reference'],
                    'tax_group_id' => $line['tax_group_id'],
                    'is_stock' => false,
                ],
            );
        }

        $sources = $goodsReceipt instanceof GoodsReceiptNote
            ? [new PurchaseInvoiceSourceData('goods_receipt_note', (int) $goodsReceipt->getKey())]
            : [];

        $adjustments = [];
        if ($this->math->compare($resolved['summary']['withholding_total'], '0.000000') > 0) {
            $adjustments[] = new InvoiceAdjustmentData(
                name: 'Withholding',
                adjustmentType: AdjustmentType::Withholding,
                effect: AdjustmentEffect::Decrease,
                amount: $resolved['summary']['withholding_total'],
                calculationType: 'fixed',
                rate: '0.000000',
                sourceAmount: $resolved['summary']['withholding_total'],
                allocationMethod: AllocationMethod::Manual,
                isSystemGenerated: true,
                description: 'Fast purchase withholding',
            );
        }

        return $this->purchaseInvoices->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: (int) $resolved['tenant_id'],
            invoiceDate: (string) $resolved['purchase_date'],
            organizationUnitId: $resolved['organization_unit_id'],
            supplierType: self::SUPPLIER_TYPE,
            supplierId: (int) $resolved['supplier']->getKey(),
            dueDate: $resolved['due_date'],
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            notes: $resolved['notes'],
            createdBy: $resolved['current_user_id'],
            sources: $sources,
            status: InvoiceStatus::Posted,
            directLines: $directLines,
            adjustments: $adjustments,
        ))->load(['lines', 'sources', 'sourceLines', 'adjustments', 'balance', 'supplier']);
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function createSupplierPayment(array $resolved, Invoice $invoice): Payment
    {
        $payment = $resolved['payment'];
        $amount = (string) $payment['amount'];

        $data = $this->purchasePayments->prepareSupplierPayment(
            tenantId: (int) $resolved['tenant_id'],
            paymentDate: (string) $resolved['purchase_date'],
            amount: $amount,
            organizationUnitId: $resolved['organization_unit_id'],
            supplierType: self::SUPPLIER_TYPE,
            supplierId: (int) $resolved['supplier']->getKey(),
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            referenceNumber: $payment['reference'] ?? $resolved['supplier_reference'],
            lines: $payment['lines'],
            allocations: [
                new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: $amount,
                    allocationDate: (string) $resolved['purchase_date'],
                    allowOverpayment: false,
                    metadata: ['fast_purchase' => true, 'supplier_reference' => $resolved['supplier_reference']],
                ),
            ],
            status: PaymentStatus::Posted,
            createdBy: $resolved['current_user_id'],
            notes: $resolved['notes'],
            bankAccountId: $payment['header_bank_account_id'],
            metadata: ['fast_purchase' => true, 'supplier_reference' => $resolved['supplier_reference']],
        );

        return $this->payments->create($data)->load(['lines', 'allocations']);
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<PostingResultData>
     */
    private function postInventoryFinance(array $resolved, GoodsReceiptNote $goodsReceipt): array
    {
        $amount = $resolved['summary']['stock_taxable_total'];
        if ($this->math->isZero($amount)) {
            return [];
        }

        return [$this->financePostings->post(new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'goods_receipt_note',
                sourceId: (int) $goodsReceipt->getKey(),
                tenantId: (int) $resolved['tenant_id'],
                organizationUnitId: $resolved['organization_unit_id'],
                sourceModule: 'purchase',
                sourceNumber: (string) $goodsReceipt->grn_number,
                sourceDate: $goodsReceipt->received_date?->toDateString() ?? (string) $resolved['purchase_date'],
            ),
            postingDate: (string) $resolved['purchase_date'],
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            lines: [
                new FinancePostingLine(null, 'Inventory', debit: $amount, profileKey: 'inventory'),
                new FinancePostingLine(null, 'Goods received payable', credit: $amount, profileKey: 'payable'),
            ],
            description: 'Fast purchase stock receipt '.$goodsReceipt->grn_number,
            postingProfileCode: 'inventory_receipt',
        ), $resolved['current_user_id'])];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<PostingResultData>
     */
    private function postInvoiceFinance(array $resolved, Invoice $invoice): array
    {
        $directTaxable = $resolved['summary']['non_stock_taxable_total'];
        $tax = $resolved['summary']['tax_total'];
        $withholding = $resolved['summary']['withholding_total'];
        if ($this->math->isZero($directTaxable) && $this->math->isZero($tax) && $this->math->isZero($withholding)) {
            return [];
        }

        $creditPayable = $this->math->sub($this->math->add($directTaxable, $tax), $withholding);
        $lines = [];
        if (! $this->math->isZero($directTaxable)) {
            $lines[] = new FinancePostingLine(null, 'Purchase expense', debit: $directTaxable, profileKey: 'expense');
        }
        if (! $this->math->isZero($tax)) {
            $lines[] = new FinancePostingLine(null, 'Input tax', debit: $tax, profileKey: 'tax_receivable');
        }
        if (! $this->math->isZero($creditPayable)) {
            $lines[] = new FinancePostingLine(null, 'Supplier payable', credit: $creditPayable, profileKey: 'payable');
        }
        if (! $this->math->isZero($withholding)) {
            $lines[] = new FinancePostingLine(null, 'Withholding payable', credit: $withholding, profileKey: 'payable');
        }

        return [$this->financePostings->post(new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'purchase_invoice',
                sourceId: (int) $invoice->getKey(),
                tenantId: (int) $invoice->tenant_id,
                organizationUnitId: $invoice->organization_unit_id,
                sourceModule: 'purchase',
                sourceNumber: (string) $invoice->invoice_number,
                sourceDate: $invoice->invoice_date?->toDateString() ?? (string) $resolved['purchase_date'],
            ),
            postingDate: (string) $resolved['purchase_date'],
            currencyId: $invoice->currency_id,
            exchangeRate: (string) $invoice->exchange_rate,
            lines: $lines,
            description: 'Fast purchase supplier invoice '.$invoice->invoice_number,
            postingProfileCode: 'purchase_invoice',
        ), $resolved['current_user_id'])];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<PostingResultData>
     */
    private function postPaymentFinance(array $resolved, Payment $payment): array
    {
        $lines = [
            new FinancePostingLine(null, 'Supplier payable', debit: (string) $payment->total_amount, profileKey: 'payable'),
        ];

        foreach ($resolved['payment']['source_accounts'] as $row) {
            /** @var FinanceAccount $account */
            $account = $row['account'];
            $lines[] = new FinancePostingLine(
                accountCode: (string) $account->code,
                accountName: (string) $account->name,
                credit: (string) $row['amount'],
                description: 'Fast purchase payment source',
            );
        }

        return [$this->financePostings->post(new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'payment_made',
                sourceId: (int) $payment->getKey(),
                tenantId: (int) $payment->tenant_id,
                organizationUnitId: $payment->organization_unit_id,
                sourceModule: 'payment',
                sourceNumber: (string) $payment->payment_number,
                sourceDate: $payment->payment_date?->toDateString() ?? (string) $resolved['purchase_date'],
            ),
            postingDate: (string) $resolved['purchase_date'],
            currencyId: $payment->currency_id,
            exchangeRate: (string) $payment->exchange_rate,
            lines: $lines,
            description: 'Fast purchase supplier payment '.$payment->payment_number,
            postingProfileCode: 'payment_made',
        ), $resolved['current_user_id'])];
    }

    /**
     * @param  list<array<string, mixed>>  $linePayloads
     * @return list<array<string, mixed>>
     */
    private function resolveLines(array $linePayloads, Supplier $supplier, int $tenantId, ?int $organizationUnitId, string $purchaseDate, ?int $currencyId, bool $lockRecords): array
    {
        $resolved = [];
        $taxLines = [];

        foreach (array_values($linePayloads) as $index => $line) {
            if (! is_array($line)) {
                throw new InvalidArgumentException('Fast purchase lines are invalid.');
            }

            $item = $this->item($tenantId, $organizationUnitId, (int) $line['item_id'], $lockRecords);
            $quantity = $this->math->normalize((string) $line['quantity']);
            $uomId = $this->resolveUomId($tenantId, $organizationUnitId, (int) $supplier->getKey(), $item, $line);
            $uom = $this->validator->uom($tenantId, $organizationUnitId, $uomId);
            $uomBasis = $this->uoms->resolveLineUom($tenantId, $item, $uomId, $quantity);
            $unitCost = array_key_exists('unit_cost', $line) && $line['unit_cost'] !== null
                ? $this->math->normalize((string) $line['unit_cost'])
                : $this->resolveUnitCost($tenantId, $organizationUnitId, $item, $currencyId, $uomId, $purchaseDate);
            if ($this->math->compare($unitCost, '0.000000') <= 0) {
                throw new InvalidArgumentException('Purchase unit cost must be greater than zero.');
            }
            $discount = $this->math->normalize((string) ($line['discount_amount'] ?? '0.000000'));
            $taxGroupId = $this->nullableInt($line['tax_group_id'] ?? null);
            if ($taxGroupId !== null) {
                $this->taxGroup($tenantId, $organizationUnitId, $taxGroupId, $lockRecords);
            }

            $lineSubtotal = $this->math->mul($quantity, $unitCost);
            if ($this->math->compare($discount, $lineSubtotal) > 0) {
                throw new InvalidArgumentException('Line discount cannot exceed line subtotal.');
            }

            $resolved[] = [
                'line_number' => $index + 1,
                'item' => $item,
                'item_id' => (int) $item->getKey(),
                'item_variant_id' => $this->nullableInt($line['item_variant_id'] ?? null),
                'description' => trim((string) ($line['description'] ?? '')) !== '' ? trim((string) $line['description']) : (string) $item->name,
                'uom_id' => (int) $uom->getKey(),
                'uom' => $uom,
                'quantity' => $quantity,
                'base_uom_id' => $uomBasis['base_uom_id'],
                'uom_conversion_factor' => $uomBasis['conversion_factor'],
                'base_quantity' => $uomBasis['base_quantity'],
                'unit_cost' => $unitCost,
                'discount_amount' => $discount,
                'tax_group_id' => $taxGroupId,
                'is_stock' => $this->isStockItem($item),
                'line_subtotal' => $lineSubtotal,
            ];

            $taxLines[] = new TaxCalculationLineData(
                lineNumber: $index + 1,
                quantity: $quantity,
                unitPrice: $unitCost,
                itemId: (int) $item->getKey(),
                taxGroupId: $taxGroupId,
                discountBeforeTax: $discount,
            );
        }

        $taxResult = $this->taxes->calculate(new TaxCalculationData(
            tenantId: $tenantId,
            documentType: 'supplier_invoice',
            documentDate: $purchaseDate,
            organizationUnitId: $organizationUnitId,
            supplierId: (int) $supplier->getKey(),
            lines: $taxLines,
        ));

        foreach ($taxResult->lineResults as $result) {
            $index = $result->lineNumber - 1;
            $withholding = $this->math->normalize($result->withholdingAmount);
            $nonWithholdingTax = $this->math->sub((string) $result->taxAmount, $withholding);
            $resolved[$index]['tax_amount'] = $this->math->normalize((string) $result->taxAmount);
            $resolved[$index]['non_withholding_tax_amount'] = $nonWithholdingTax;
            $resolved[$index]['withholding_amount'] = $withholding;
            $resolved[$index]['line_total'] = $this->math->normalize((string) $result->totalAmount);
            $resolved[$index]['taxes'] = array_map(static fn ($tax): array => [
                'tax_id' => $tax->taxId,
                'tax_code' => $tax->taxCode,
                'tax_name' => $tax->taxName,
                'rate' => $tax->rate,
                'tax_amount' => $tax->taxAmount,
                'is_withholding' => $tax->isWithholding,
            ], $result->taxes);
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, string>
     */
    private function summary(array $lines): array
    {
        $summary = [
            'subtotal' => '0.000000',
            'discount_total' => '0.000000',
            'tax_total' => '0.000000',
            'withholding_total' => '0.000000',
            'grand_total' => '0.000000',
            'paid_total' => '0.000000',
            'balance_due' => '0.000000',
            'stock_taxable_total' => '0.000000',
            'non_stock_taxable_total' => '0.000000',
        ];

        foreach ($lines as $line) {
            $taxable = $this->math->sub($line['line_subtotal'], $line['discount_amount']);
            $summary['subtotal'] = $this->math->add($summary['subtotal'], $line['line_subtotal']);
            $summary['discount_total'] = $this->math->add($summary['discount_total'], $line['discount_amount']);
            $summary['tax_total'] = $this->math->add($summary['tax_total'], $line['non_withholding_tax_amount']);
            $summary['withholding_total'] = $this->math->add($summary['withholding_total'], $line['withholding_amount']);
            $summary['grand_total'] = $this->math->add($summary['grand_total'], $line['line_total']);

            if ((bool) $line['is_stock']) {
                $summary['stock_taxable_total'] = $this->math->add($summary['stock_taxable_total'], $taxable);
            } else {
                $summary['non_stock_taxable_total'] = $this->math->add($summary['non_stock_taxable_total'], $taxable);
            }
        }

        $summary['balance_due'] = $summary['grand_total'];

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $summary
     * @return array<string, mixed>
     */
    private function resolvePayment(array $payload, bool $recordPayment, array &$summary, int $tenantId, ?int $organizationUnitId, bool $lockRecords): array
    {
        if (! $recordPayment) {
            return ['amount' => '0.000000', 'reference' => null, 'lines' => [], 'source_accounts' => [], 'header_bank_account_id' => null];
        }

        $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $linePayloads = is_array($payment['lines'] ?? null) && $payment['lines'] !== []
            ? $payment['lines']
            : [[
                'amount' => $payment['amount'] ?? null,
                'payment_method_id' => $payment['payment_method_id'] ?? null,
                'source_account_id' => $payment['source_account_id'] ?? null,
                'reference' => $payment['reference'] ?? null,
                'instrument_number' => $payment['instrument_number'] ?? $payment['cheque_number'] ?? $payment['card_reference'] ?? null,
                'instrument_date' => $payment['instrument_date'] ?? $payment['cheque_date'] ?? null,
                'external_bank_name' => $payment['external_bank_name'] ?? null,
                'external_bank_branch' => $payment['external_bank_branch'] ?? null,
            ]];

        $lines = [];
        $sourceAccounts = [];
        $amount = '0.000000';
        $headerBankAccountId = null;

        foreach ($linePayloads as $line) {
            if (! is_array($line) || ($line['amount'] ?? null) === null) {
                throw new InvalidArgumentException('Payment amount is required when recording payment.');
            }

            $lineAmount = $this->math->normalize((string) $line['amount']);
            $accountId = $this->nullableInt($line['source_account_id'] ?? null);
            if ($accountId === null) {
                throw new InvalidArgumentException('Payment source account is required.');
            }

            $account = $this->paymentAccount($tenantId, $organizationUnitId, $accountId, $lockRecords);
            $methodId = $this->nullableInt($line['payment_method_id'] ?? null);
            if ($methodId !== null) {
                $this->paymentMethod($tenantId, $organizationUnitId, $methodId, $lockRecords);
            }

            if ((bool) $account->is_bank_account && $headerBankAccountId === null) {
                $headerBankAccountId = (int) $account->getKey();
            }

            $lines[] = new PaymentLineData(
                amount: $lineAmount,
                paymentMethodId: $methodId,
                referenceNumber: $this->nullableString($line['reference'] ?? null),
                internalBankAccountId: (bool) $account->is_bank_account ? (int) $account->getKey() : null,
                instrumentDirection: 'outbound',
                externalBankName: $this->nullableString($line['external_bank_name'] ?? null),
                externalBankBranch: $this->nullableString($line['external_bank_branch'] ?? null),
                instrumentNumber: $this->nullableString($line['instrument_number'] ?? null),
                instrumentDate: $this->nullableString($line['instrument_date'] ?? null),
                metadata: ['source_account_id' => (int) $account->getKey()],
            );
            $sourceAccounts[] = ['account' => $account, 'amount' => $lineAmount];
            $amount = $this->math->add($amount, $lineAmount);
        }

        if ($this->math->compare($amount, $summary['grand_total']) > 0) {
            throw new InvalidArgumentException('Payment amount cannot exceed supplier invoice total.');
        }

        $summary['paid_total'] = $amount;
        $summary['balance_due'] = $this->math->sub($summary['grand_total'], $amount);

        return [
            'amount' => $amount,
            'reference' => $this->nullableString($payment['reference'] ?? null),
            'lines' => $lines,
            'source_accounts' => $sourceAccounts,
            'header_bank_account_id' => $headerBankAccountId,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function validateMode(array $lines, bool $receiveStock, bool $createInvoice, bool $recordPayment): void
    {
        $hasStock = false;
        $hasNonStock = false;
        foreach ($lines as $line) {
            $hasStock = $hasStock || (bool) $line['is_stock'];
            $hasNonStock = $hasNonStock || ! (bool) $line['is_stock'];
        }

        if ($recordPayment && ! $createInvoice) {
            throw new InvalidArgumentException('Payments require supplier invoice creation.');
        }
        if ($hasStock && ! $receiveStock) {
            throw new InvalidArgumentException('Stock lines require receiving stock now.');
        }
        if ($hasNonStock && ! $createInvoice) {
            throw new InvalidArgumentException('Service and expense lines require supplier invoice creation.');
        }
        if (! $hasStock && $receiveStock) {
            throw new InvalidArgumentException('Receive stock now requires at least one stock item.');
        }
        if (! $receiveStock && ! $createInvoice) {
            throw new InvalidArgumentException('Fast purchase must create a stock receipt or supplier invoice.');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function mode(array $lines, bool $receiveStock, bool $createInvoice, bool $recordPayment): string
    {
        $hasStock = collect($lines)->contains(fn (array $line): bool => (bool) $line['is_stock']);
        $hasNonStock = collect($lines)->contains(fn (array $line): bool => ! (bool) $line['is_stock']);

        if ($receiveStock && ! $createInvoice) {
            return 'receive_only';
        }
        if ($hasNonStock && ! $hasStock) {
            return $recordPayment ? 'direct_purchase_paid' : 'direct_purchase';
        }
        if ($recordPayment) {
            return $hasNonStock ? 'mixed_cash_purchase' : 'cash_purchase';
        }

        return $hasNonStock ? 'mixed_credit_purchase' : 'credit_purchase';
    }

    private function validateCredit(Supplier $supplier, bool $createInvoice, string $grandTotal, string $paidTotal): void
    {
        if ($createInvoice && $this->math->compare($paidTotal, $grandTotal) < 0 && ! (bool) $supplier->is_credit_allowed) {
            throw new InvalidArgumentException('Supplier is not enabled for credit purchases.');
        }
    }

    private function isStockItem(Item $item): bool
    {
        $type = $item->item_type instanceof ItemType ? $item->item_type : ItemType::from((string) $item->item_type);

        return (bool) $item->is_stockable && ! in_array($type, [ItemType::NonStock, ItemType::Service, ItemType::Labour], true);
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function resolveUomId(int $tenantId, ?int $organizationUnitId, int $supplierId, Item $item, array $line): int
    {
        $requested = $this->nullableInt($line['uom_id'] ?? null);
        if ($requested !== null) {
            return $requested;
        }

        $mapping = SupplierItemMapping::query()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->where('item_id', $item->getKey())
            ->where('is_active', true)
            ->whereNotNull('default_purchase_uom_id')
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            }))
            ->orderByDesc('is_preferred')
            ->first();
        if ($mapping instanceof SupplierItemMapping) {
            return (int) $mapping->default_purchase_uom_id;
        }

        $unit = ItemUnit::query()
            ->where('tenant_id', $tenantId)
            ->where('item_id', $item->getKey())
            ->where('unit_role', ItemUnitRole::Purchase->value)
            ->where('is_active', true)
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            }))
            ->orderByDesc('is_default')
            ->first();
        if ($unit instanceof ItemUnit) {
            return (int) $unit->uom_id;
        }

        $baseUomId = (int) ($item->base_uom_id ?: 0);
        if ($baseUomId < 1) {
            throw new InvalidArgumentException('Purchase item requires a UOM.');
        }

        return $baseUomId;
    }

    private function resolveUnitCost(int $tenantId, ?int $organizationUnitId, Item $item, ?int $currencyId, int $uomId, string $purchaseDate): string
    {
        foreach ([ItemPriceType::Purchase, ItemPriceType::Cost, ItemPriceType::Standard] as $type) {
            $price = ItemPrice::query()
                ->where('tenant_id', $tenantId)
                ->where('item_id', $item->getKey())
                ->where('price_type', $type->value)
                ->where('is_active', true)
                ->when($currencyId !== null, fn ($query) => $query->where('currency_id', $currencyId))
                ->where(function ($query) use ($uomId): void {
                    $query->whereNull('uom_id')->orWhere('uom_id', $uomId);
                })
                ->where(function ($query) use ($purchaseDate): void {
                    $query->whereNull('effective_from')->orWhere('effective_from', '<=', $purchaseDate);
                })
                ->where(function ($query) use ($purchaseDate): void {
                    $query->whereNull('effective_to')->orWhere('effective_to', '>=', $purchaseDate);
                })
                ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                    $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
                }))
                ->orderByRaw('case when uom_id = ? then 0 else 1 end', [$uomId])
                ->latest('effective_from')
                ->first();
            if ($price instanceof ItemPrice) {
                return $this->math->normalize((string) $price->amount);
            }
        }

        return '0.000000';
    }

    private function supplier(int $tenantId, ?int $organizationUnitId, int $supplierId, bool $lockRecords): Supplier
    {
        $supplier = Supplier::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($supplierId);

        return $this->validator->supplier($tenantId, $organizationUnitId, (int) $supplier->getKey());
    }

    private function item(int $tenantId, ?int $organizationUnitId, int $itemId, bool $lockRecords): Item
    {
        $item = Item::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($itemId);

        return $this->validator->item($tenantId, $organizationUnitId, (int) $item->getKey());
    }

    private function warehouse(int $tenantId, ?int $organizationUnitId, int $warehouseId, bool $lockRecords): WarehouseModel
    {
        $warehouse = WarehouseModel::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($warehouseId);

        return $this->validator->warehouse($tenantId, $organizationUnitId, (int) $warehouse->getKey());
    }

    private function warehouseLocation(int $tenantId, ?int $organizationUnitId, int $warehouseId, int $locationId, bool $lockRecords): WarehouseLocationModel
    {
        $location = WarehouseLocationModel::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($locationId);

        return $this->validator->warehouseLocation($tenantId, $organizationUnitId, $warehouseId, (int) $location->getKey());
    }

    private function currencyId(array $payload, Supplier $supplier, int $tenantId, ?int $organizationUnitId, bool $lockRecords): ?int
    {
        $currencyId = $this->nullableInt($payload['currency_id'] ?? null) ?? $supplier->default_currency_id;
        if ($currencyId === null) {
            return null;
        }

        CurrencyModel::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($currencyId);
        $this->validator->currency($tenantId, $organizationUnitId, (int) $currencyId);

        return (int) $currencyId;
    }

    private function taxGroup(int $tenantId, ?int $organizationUnitId, int $taxGroupId, bool $lockRecords): TaxGroup
    {
        $group = TaxGroup::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($taxGroupId);
        $this->validator->assertTenantOrg((int) $group->tenant_id, $group->organization_unit_id, $tenantId, $organizationUnitId);
        if (! (bool) $group->active) {
            throw new InvalidArgumentException('Tax group must be active.');
        }

        return $group;
    }

    private function paymentMethod(int $tenantId, ?int $organizationUnitId, int $methodId, bool $lockRecords): PaymentMethod
    {
        $method = PaymentMethod::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($methodId);
        if ($method->tenant_id !== null && (int) $method->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Payment method belongs to a different tenant.');
        }
        if ($method->organization_unit_id !== null && $organizationUnitId !== null && (int) $method->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Payment method belongs to a different organization unit.');
        }
        if (! (bool) $method->is_active) {
            throw new InvalidArgumentException('Payment method is inactive.');
        }

        return $method;
    }

    private function paymentAccount(int $tenantId, ?int $organizationUnitId, int $accountId, bool $lockRecords): FinanceAccount
    {
        $account = FinanceAccount::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($accountId);
        if ((int) $account->tenant_id !== $tenantId || $account->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Payment source account belongs to a different scope.');
        }
        if (! (bool) $account->is_active || ! (bool) $account->is_posting_account) {
            throw new InvalidArgumentException('Payment source account must be active and postable.');
        }
        if (! (bool) $account->is_cash_account && ! (bool) $account->is_bank_account) {
            throw new InvalidArgumentException('Payment source account must be a cash or bank account.');
        }

        return $account;
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function completedReference(array $resolved, string $referenceHash): ?AuditLogModel
    {
        return AuditLogModel::query()
            ->where('tenant_id', $resolved['tenant_id'])
            ->when(
                $resolved['organization_unit_id'] === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $resolved['organization_unit_id']),
            )
            ->where('event', 'fast_purchase.completed')
            ->where('auditable_type', 'fast_purchase_reference')
            ->where('auditable_id', $referenceHash)
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function previewResponse(array $resolved): array
    {
        return [
            'mode' => $resolved['mode'],
            'options' => $resolved['options'],
            'summary' => $resolved['summary'],
            'supplier' => $this->modelSummary($resolved['supplier'], ['supplier_number', 'code', 'name', 'display_name']),
            'lines' => array_map(fn (array $line): array => $this->linePreview($line), $resolved['lines']),
            'documents' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @param  array<string, mixed>  $documents
     * @return array<string, mixed>
     */
    private function createResponse(array $resolved, array $documents): array
    {
        /** @var GoodsReceiptNote|null $goodsReceipt */
        $goodsReceipt = $documents['goods_receipt'];
        /** @var Invoice|null $invoice */
        $invoice = $documents['supplier_invoice'];
        /** @var Payment|null $payment */
        $payment = $documents['supplier_payment'];
        $financePostings = $documents['finance_postings'];
        $inventoryMovements = $goodsReceipt instanceof GoodsReceiptNote ? $goodsReceipt->lines->pluck('inventoryMovement')->filter()->values() : collect();

        return [
            'supplier_reference' => $resolved['supplier_reference'],
            'mode' => $resolved['mode'],
            'options' => $resolved['options'],
            'summary' => $this->summaryWithPaid($resolved['summary'], $invoice, $payment),
            'supplier' => $this->modelSummary($resolved['supplier'], ['supplier_number', 'code', 'name', 'display_name']),
            'lines' => array_map(fn (array $line): array => $this->linePreview($line), $resolved['lines']),
            'documents' => [
                'goods_receipt' => $goodsReceipt instanceof GoodsReceiptNote ? $this->goodsReceiptRef($goodsReceipt) : null,
                'supplier_invoice' => $invoice instanceof Invoice ? $this->invoiceRef($invoice) : null,
                'supplier_payment' => $payment instanceof Payment ? $this->paymentRef($payment) : null,
                'inventory_transaction' => $inventoryMovements->first() instanceof InventoryMovement ? $this->inventoryMovementRef($inventoryMovements->first()) : null,
                'inventory_transactions' => $inventoryMovements->map(fn (InventoryMovement $movement): array => $this->inventoryMovementRef($movement))->all(),
                'finance_posting' => isset($financePostings[0]) ? $this->financePostingRef($financePostings[0]) : null,
                'finance_postings' => array_map(fn (PostingResultData $posting): array => $this->financePostingRef($posting), $financePostings),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, string>
     */
    private function summaryWithPaid(array $summary, ?Invoice $invoice, ?Payment $payment): array
    {
        if ($invoice instanceof Invoice) {
            $summary['paid_total'] = (string) $invoice->paid_total;
            $summary['balance_due'] = (string) $invoice->balance_due;
        }
        if ($payment instanceof Payment) {
            $summary['paid_total'] = (string) $payment->allocated_amount;
            $summary['balance_due'] = $this->math->sub($summary['grand_total'], (string) $payment->allocated_amount);
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    private function linePreview(array $line): array
    {
        return [
            'line_number' => $line['line_number'],
            'item' => $this->modelSummary($line['item'], ['code', 'sku', 'name']),
            'uom' => $this->modelSummary($line['uom'], ['code', 'name', 'symbol']),
            'description' => $line['description'],
            'is_stock' => $line['is_stock'],
            'quantity' => $line['quantity'],
            'base_quantity' => $line['base_quantity'],
            'unit_cost' => $line['unit_cost'],
            'discount_amount' => $line['discount_amount'],
            'tax_amount' => $line['non_withholding_tax_amount'],
            'withholding_amount' => $line['withholding_amount'],
            'line_total' => $line['line_total'],
            'taxes' => $line['taxes'],
        ];
    }

    private function goodsReceiptRef(GoodsReceiptNote $goodsReceipt): array
    {
        return ['id' => (int) $goodsReceipt->getKey(), 'number' => (string) $goodsReceipt->grn_number, 'status' => $this->enumValue($goodsReceipt->status), 'url' => '/purchase/goods-receipts/'.$goodsReceipt->getKey()];
    }

    private function invoiceRef(Invoice $invoice): array
    {
        return ['id' => (int) $invoice->getKey(), 'number' => (string) $invoice->invoice_number, 'status' => $this->enumValue($invoice->status), 'url' => '/invoices/'.$invoice->getKey()];
    }

    private function paymentRef(Payment $payment): array
    {
        return ['id' => (int) $payment->getKey(), 'number' => (string) $payment->payment_number, 'status' => $this->enumValue($payment->status), 'url' => '/payments/'.$payment->getKey()];
    }

    private function inventoryMovementRef(InventoryMovement $movement): array
    {
        return ['id' => (int) $movement->getKey(), 'number' => (string) $movement->movement_number, 'status' => $this->enumValue($movement->status), 'url' => '/inventory/movements/'.$movement->getKey()];
    }

    private function financePostingRef(PostingResultData $posting): array
    {
        return ['id' => $posting->journalId, 'number' => $posting->journalNumber, 'status' => $posting->status, 'url' => '/finance/journals/'.$posting->journalId, 'total_debit' => $posting->totalDebit, 'total_credit' => $posting->totalCredit];
    }

    /**
     * @param  list<string>  $fields
     */
    private function modelSummary(mixed $model, array $fields): ?array
    {
        if (! is_object($model) || ! method_exists($model, 'getKey')) {
            return null;
        }

        $data = ['id' => (int) $model->getKey()];
        foreach ($fields as $field) {
            if (($model->{$field} ?? null) !== null && $model->{$field} !== '') {
                $data[$field] = $model->{$field};
            }
        }
        if (! isset($data['name']) && isset($data['display_name'])) {
            $data['name'] = $data['display_name'];
        }

        return $data;
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @param  array<string, mixed>  $response
     */
    private function writeAuditLog(array $resolved, string $referenceHash, string $requestHash, array $response): void
    {
        $result = $this->audit->execute(new AuditLogActivityData(
            event: 'fast_purchase.completed',
            auditableType: 'fast_purchase_reference',
            auditableId: $referenceHash,
            tenantId: (int) $resolved['tenant_id'],
            organizationUnitId: $resolved['organization_unit_id'],
            userId: $resolved['current_user_id'],
            newValues: [
                'response' => $response,
                'documents' => $response['documents'],
                'summary' => $response['summary'],
            ],
            metadata: [
                'request_hash' => $requestHash,
                'supplier_id' => (int) $resolved['supplier']->getKey(),
                'supplier_reference' => $resolved['supplier_reference'],
                'purchase_date' => $resolved['purchase_date'],
            ],
            tags: ['purchase', 'fast_purchase'],
            occurredAt: now()->toDateTimeString(),
        ));

        if ($result->isFailure()) {
            throw new InvalidArgumentException('Fast purchase audit log could not be written.');
        }
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function referenceHash(array $resolved): string
    {
        $reference = trim((string) $resolved['supplier_reference']);
        if ($reference === '') {
            throw new InvalidArgumentException('Supplier reference is required for fast purchase submission.');
        }

        return hash('sha256', implode('|', [
            (string) $resolved['tenant_id'],
            (string) ($resolved['organization_unit_id'] ?? 'none'),
            (string) $resolved['supplier']->getKey(),
            mb_strtolower($reference),
        ]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requestHash(array $payload): string
    {
        unset($payload['current_user_id']);
        $this->recursiveSort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recursiveSort(array &$payload): void
    {
        ksort($payload);
        foreach ($payload as &$value) {
            if (is_array($value)) {
                $this->recursiveSort($value);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function rejectClientAuthorityFields(array $payload): void
    {
        foreach (['subtotal', 'discount_total', 'tax_total', 'withholding_total', 'grand_total', 'paid_total', 'balance_due', 'status', 'posting_status', 'approval_status', 'finance_account_id', 'payable_account_id', 'inventory_account_id', 'base_quantity', 'base_uom_quantity'] as $key) {
            if (array_key_exists($key, $payload)) {
                throw new InvalidArgumentException('Fast purchase totals, statuses, quantities, and finance accounts are server controlled.');
            }
        }

        foreach (($payload['lines'] ?? []) as $line) {
            if (! is_array($line)) {
                continue;
            }
            foreach (['line_total', 'tax_amount', 'withholding_amount', 'base_quantity', 'base_uom_quantity', 'finance_account_id', 'status'] as $key) {
                if (array_key_exists($key, $line)) {
                    throw new InvalidArgumentException('Fast purchase line totals, statuses, base quantities, and finance accounts are server controlled.');
                }
            }
        }
    }

    private function dueDate(string $purchaseDate, string $paymentTerms, mixed $explicitDueDate): string
    {
        if ($explicitDueDate !== null && trim((string) $explicitDueDate) !== '') {
            return (string) $explicitDueDate;
        }
        if (preg_match('/(\d+)/', $paymentTerms, $matches) === 1) {
            return CarbonImmutable::parse($purchaseDate)->addDays((int) $matches[1])->toDateString();
        }

        return $purchaseDate;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function notes(array $payload): ?string
    {
        $notes = trim((string) ($payload['notes'] ?? ''));
        foreach ([
            'Supplier reference' => trim((string) ($payload['supplier_reference'] ?? '')),
            'Payment terms' => trim((string) ($payload['payment_terms'] ?? '')),
        ] as $label => $value) {
            if ($value !== '') {
                $notes = trim($notes."\n{$label}: {$value}");
            }
        }

        return $notes !== '' ? $notes : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $resolved = trim((string) ($value ?? ''));

        return $resolved !== '' ? $resolved : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function warehouseOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return WarehouseModel::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            }))
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'code', 'name', 'is_default'])
            ->map(fn (WarehouseModel $warehouse): array => ['id' => (int) $warehouse->getKey(), 'code' => $warehouse->code, 'name' => $warehouse->name, 'is_default' => (bool) $warehouse->is_default])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function currencyOptions(string $search, int $limit): array
    {
        return CurrencyModel::query()
            ->where('is_active', true)
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'name', 'symbol', 'decimal_places'])
            ->map(fn (CurrencyModel $currency): array => ['id' => (int) $currency->getKey(), 'code' => $currency->code, 'name' => $currency->name, 'symbol' => $currency->symbol, 'decimal_places' => $currency->decimal_places])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentMethodOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('direction_allowed', 'outbound')->orWhere('direction_allowed', 'both');
            })
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->when($organizationUnitId !== null, fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            }))
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'code', 'name', 'method_type', 'requires_reference', 'requires_bank_account'])
            ->map(fn (PaymentMethod $method): array => ['id' => (int) $method->getKey(), 'code' => $method->code, 'name' => $method->name, 'method_type' => $this->enumValue($method->method_type), 'requires_reference' => (bool) $method->requires_reference, 'requires_bank_account' => (bool) $method->requires_bank_account])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentAccountOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return FinanceAccount::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where('organization_unit_id', $organizationUnitId))
            ->where('is_active', true)
            ->where('is_posting_account', true)
            ->where(function ($query): void {
                $query->where('is_cash_account', true)->orWhere('is_bank_account', true);
            })
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'name', 'is_cash_account', 'is_bank_account'])
            ->map(fn (FinanceAccount $account): array => ['id' => (int) $account->getKey(), 'code' => $account->code, 'name' => $account->name, 'is_cash_account' => (bool) $account->is_cash_account, 'is_bank_account' => (bool) $account->is_bank_account])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function taxGroupOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return TaxGroup::query()
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            }))
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'code', 'name', 'is_default'])
            ->map(fn (TaxGroup $group): array => ['id' => (int) $group->getKey(), 'code' => $group->code, 'name' => $group->name, 'is_default' => (bool) $group->is_default])
            ->all();
    }
}
