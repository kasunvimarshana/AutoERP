<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Audit\DTOs\AuditLogActivityData;
use Modules\Audit\Models\AuditLogModel;
use Modules\Audit\Services\AuditLogs\LogActivityService;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerCreditProfile;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Services\StockAvailabilityService;
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
use Modules\Item\Models\ItemUsageRule;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentCreationService;
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
use Modules\Sales\Validators\SalesValidationService;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\Models\TaxGroup;
use Modules\Tax\Services\TaxCalculationService;
use Modules\Tax\Services\TaxDocumentIntegrationService;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class FastSalesService
{
    private const CUSTOMER_TYPE = 'customer';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesValidationService $validator,
        private readonly StockAvailabilityService $stockAvailability,
        private readonly TaxCalculationService $taxes,
        private readonly TaxDocumentIntegrationService $taxDocuments,
        private readonly SalesOrderService $salesOrders,
        private readonly SalesDeliveryService $deliveries,
        private readonly SalesInvoiceIntegrationService $salesInvoices,
        private readonly SalesPaymentPreparationService $salesPayments,
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
                'transaction_date' => now()->toDateString(),
                'exchange_rate' => '1.000000',
            ],
            'endpoints' => [
                'customer_search' => '/api/v1/customers/lookup/active',
                'item_search' => '/api/v1/items/lookup',
                'preview' => '/api/v1/sales/fast-sales/preview',
                'create' => '/api/v1/sales/fast-sales',
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
                    throw new InvalidArgumentException('Customer reference was already used for a different fast sale.');
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
        $transactionDate = (string) $payload['transaction_date'];
        $customer = $this->customer($tenantId, $organizationUnitId, (int) $payload['customer_id'], $lockRecords);
        $customerReference = trim((string) ($payload['customer_reference'] ?? ''));
        $currencyId = $this->currencyId($payload, $customer, $tenantId, $organizationUnitId, $lockRecords);
        $exchangeRate = $this->math->normalize((string) ($payload['exchange_rate'] ?? '1.000000'));
        $warehouseId = $this->nullableInt($payload['warehouse_id'] ?? null);
        $warehouseLocationId = $this->nullableInt($payload['warehouse_location_id'] ?? null);
        $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
        $createOrderOnly = (bool) ($options['create_sales_order_only'] ?? false);
        $deliverItemsNow = (bool) ($options['deliver_items_now'] ?? false);
        $createInvoice = (bool) ($options['create_customer_invoice_now'] ?? false);
        $recordReceipt = (bool) ($options['record_customer_receipt_now'] ?? false);

        if (($deliverItemsNow || $createOrderOnly) && $warehouseId === null) {
            throw new InvalidArgumentException('Warehouse is required for stock sales workflows.');
        }

        if ($warehouseId !== null) {
            $this->warehouse($tenantId, $organizationUnitId, $warehouseId, $lockRecords);
            if ($warehouseLocationId !== null) {
                $this->warehouseLocation($tenantId, $organizationUnitId, $warehouseId, $warehouseLocationId, $lockRecords);
            }
        }

        $lines = $this->resolveLines(
            is_array($payload['lines'] ?? null) ? $payload['lines'] : [],
            $customer,
            $tenantId,
            $organizationUnitId,
            $transactionDate,
            $currencyId,
            $warehouseId,
            $warehouseLocationId,
            $deliverItemsNow,
            $lockRecords,
        );
        $this->validateMode($lines, $createOrderOnly, $deliverItemsNow, $createInvoice, $recordReceipt);

        $summary = $this->summary($lines);
        $payment = $this->resolvePayment($payload, $recordReceipt, $summary, $tenantId, $organizationUnitId, $lockRecords);
        $this->validateCredit($customer, $summary, $payment['amount']);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'current_user_id' => $this->nullableInt($payload['current_user_id'] ?? null),
            'customer' => $customer,
            'customer_reference' => $customerReference,
            'transaction_date' => $transactionDate,
            'due_date' => $this->dueDate($transactionDate, (string) ($payload['payment_terms'] ?? ''), $payload['due_date'] ?? null),
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $warehouseLocationId,
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'payment_terms' => trim((string) ($payload['payment_terms'] ?? '')),
            'notes' => $this->notes($payload),
            'options' => [
                'create_sales_order_only' => $createOrderOnly,
                'deliver_items_now' => $deliverItemsNow,
                'create_customer_invoice_now' => $createInvoice,
                'record_customer_receipt_now' => $recordReceipt,
            ],
            'mode' => $this->mode($lines, $createOrderOnly, $deliverItemsNow, $createInvoice, $recordReceipt),
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
                $financePostings = array_merge($financePostings, $this->postPaymentFinance($resolved, $payment));
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

    /**
     * @param  array<string, mixed>  $resolved
     */
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

    /**
     * @param  array<string, mixed>  $resolved
     */
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

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function createCustomerInvoice(array $resolved, ?SalesDelivery $delivery): Invoice
    {
        $directLines = [];
        $stockLines = array_values(array_filter($resolved['lines'], static fn (array $line): bool => (bool) $line['is_stock']));
        $deliveryLines = $delivery instanceof SalesDelivery
            ? $delivery->loadMissing('lines')->lines->values()
            : collect();

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
            if ((bool) $line['is_stock']) {
                continue;
            }

            $directLines[] = $this->invoiceLine($line, false);
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

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function createCustomerReceipt(array $resolved, Invoice $invoice): Payment
    {
        $payment = $resolved['payment'];
        $amount = (string) $payment['amount'];

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
            allocations: [
                new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: $amount,
                    allocationDate: (string) $resolved['transaction_date'],
                    allowOverpayment: false,
                    metadata: ['fast_sales' => true, 'customer_reference' => $resolved['customer_reference']],
                ),
            ],
            status: PaymentStatus::Posted,
            createdBy: $resolved['current_user_id'],
            notes: $resolved['notes'],
            bankAccountId: $payment['header_bank_account_id'],
            metadata: ['fast_sales' => true, 'customer_reference' => $resolved['customer_reference']],
        );

        return $this->payments->create($data)->load(['lines', 'allocations']);
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<PostingResultData>
     */
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

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<PostingResultData>
     */
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
        $lines = [
            new FinancePostingLine(null, 'Customer receivable', debit: $grossReceivable, profileKey: 'receivable'),
        ];
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
            $context = $this->taxDocuments->withholdingPostingContextForInvoice(
                $invoice,
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

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<PostingResultData>
     */
    private function postPaymentFinance(array $resolved, Payment $payment): array
    {
        $lines = [];
        foreach ($resolved['payment']['destination_accounts'] as $row) {
            /** @var FinanceAccount $account */
            $account = $row['account'];
            $lines[] = new FinancePostingLine(
                accountCode: (string) $account->code,
                accountName: (string) $account->name,
                debit: (string) $row['amount'],
                description: 'Fast sales receipt account',
            );
        }
        $lines[] = new FinancePostingLine(null, 'Customer receivable', credit: (string) $payment->total_amount, profileKey: 'receivable');

        return [$this->financePostings->post(new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'payment_received',
                sourceId: (int) $payment->getKey(),
                tenantId: (int) $payment->tenant_id,
                organizationUnitId: $payment->organization_unit_id,
                sourceModule: 'payment',
                sourceNumber: (string) $payment->payment_number,
                sourceDate: $payment->payment_date?->toDateString() ?? (string) $resolved['transaction_date'],
            ),
            postingDate: (string) $resolved['transaction_date'],
            currencyId: $payment->currency_id,
            exchangeRate: (string) $payment->exchange_rate,
            lines: $lines,
            description: 'Fast sales customer receipt '.$payment->payment_number,
            postingProfileCode: 'payment_received',
        ), $resolved['current_user_id'])];
    }

    /**
     * @param  list<array<string, mixed>>  $linePayloads
     * @return list<array<string, mixed>>
     */
    private function resolveLines(
        array $linePayloads,
        Customer $customer,
        int $tenantId,
        ?int $organizationUnitId,
        string $transactionDate,
        ?int $currencyId,
        ?int $warehouseId,
        ?int $warehouseLocationId,
        bool $deliverItemsNow,
        bool $lockRecords,
    ): array {
        if ($linePayloads === []) {
            throw new InvalidArgumentException('Fast sales requires at least one line.');
        }

        $resolved = [];
        $taxLines = [];

        foreach (array_values($linePayloads) as $index => $line) {
            if (! is_array($line)) {
                throw new InvalidArgumentException('Fast sales lines are invalid.');
            }

            $item = $this->item($tenantId, $organizationUnitId, (int) $line['item_id'], $lockRecords);
            $this->assertSalesUsage($item, $organizationUnitId);
            $variantId = $this->nullableInt($line['item_variant_id'] ?? null);
            if ($variantId !== null) {
                $this->validator->itemVariant($tenantId, $organizationUnitId, (int) $item->getKey(), $variantId);
            }

            $quantity = $this->math->normalize((string) $line['quantity']);
            $uomId = $this->resolveUomId($tenantId, $organizationUnitId, $item, $line);
            $uomBasis = $this->validator->resolveUom($tenantId, $organizationUnitId, $item, $uomId, $quantity);
            $uom = $this->validator->resolveUom($tenantId, $organizationUnitId, $item, $uomId, '1.000000');
            $unitPrice = $this->resolveUnitPrice(
                $tenantId,
                $organizationUnitId,
                $item,
                $variantId,
                $currencyId,
                $uomId,
                $transactionDate,
            );
            if ($this->math->compare($unitPrice, '0.000000') <= 0) {
                throw new InvalidArgumentException('Sales unit price must be configured and greater than zero.');
            }
            if (array_key_exists('unit_price', $line) && $line['unit_price'] !== null) {
                $requestedPrice = $this->math->normalize((string) $line['unit_price']);
                if ($this->math->compare($requestedPrice, $unitPrice) !== 0) {
                    throw new InvalidArgumentException('Manual price overrides are not permitted in fast sales.');
                }
            }

            $discount = $this->math->normalize((string) ($line['discount_amount'] ?? '0.000000'));
            $taxGroupId = $this->nullableInt($line['tax_group_id'] ?? null)
                ?? $this->defaultTaxGroupId($item);
            if ($taxGroupId !== null) {
                $this->taxGroup($tenantId, $organizationUnitId, $taxGroupId, $lockRecords);
            }

            $isStock = $this->isStockItem($item);
            $lineSubtotal = $this->math->mul($quantity, $unitPrice);
            if ($this->math->compare($discount, $lineSubtotal) > 0) {
                throw new InvalidArgumentException('Sales line discount cannot exceed line subtotal.');
            }

            $availableBaseQuantity = null;
            $availableQuantity = null;
            if ($isStock && $warehouseId !== null) {
                $this->lockStockBalance(
                    $tenantId,
                    $organizationUnitId,
                    (int) $item->getKey(),
                    $warehouseId,
                    $variantId,
                    $warehouseLocationId,
                    $lockRecords,
                );
                $availability = $this->stockAvailability->availability(new StockBalanceData(
                    tenantId: $tenantId,
                    itemId: (int) $item->getKey(),
                    warehouseId: $warehouseId,
                    organizationUnitId: $organizationUnitId,
                    itemVariantId: $variantId,
                    warehouseLocationId: $warehouseLocationId,
                ));
                $availableBaseQuantity = $this->math->normalize($availability->quantityAvailable);
                $availableQuantity = $this->math->div($availableBaseQuantity, $uomBasis['factor'], 6);
                if ($deliverItemsNow
                    && $this->math->compare($uomBasis['base_quantity'], $availableBaseQuantity) > 0) {
                    throw new InvalidArgumentException('Insufficient stock is available for delivery.');
                }
            }

            $resolved[] = [
                'line_number' => $index + 1,
                'item' => $item,
                'item_id' => (int) $item->getKey(),
                'item_variant_id' => $variantId,
                'description' => trim((string) ($line['description'] ?? '')) !== '' ? trim((string) $line['description']) : (string) $item->name,
                'uom_id' => $uomId,
                'uom' => $uom['ordered_uom_id'] ?? null,
                'uom_model' => $this->validator->resolveUom($tenantId, $organizationUnitId, $item, $uomId, '1.000000'),
                'quantity' => $quantity,
                'base_uom_id' => $uomBasis['base_uom_id'],
                'uom_conversion_factor' => $uomBasis['factor'],
                'base_quantity' => $uomBasis['base_quantity'],
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_group_id' => $taxGroupId,
                'is_stock' => $isStock,
                'line_type' => $this->invoiceLineType($item),
                'line_subtotal' => $lineSubtotal,
                'available_base_quantity' => $availableBaseQuantity,
                'available_quantity' => $availableQuantity,
            ];

            $taxLines[] = new TaxCalculationLineData(
                lineNumber: $index + 1,
                quantity: $quantity,
                unitPrice: $unitPrice,
                itemId: (int) $item->getKey(),
                taxGroupId: $taxGroupId,
                discountBeforeTax: $discount,
            );
        }

        $taxResult = $this->taxes->calculate(new TaxCalculationData(
            tenantId: $tenantId,
            documentType: 'sales_invoice',
            documentDate: $transactionDate,
            organizationUnitId: $organizationUnitId,
            customerId: (int) $customer->getKey(),
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
            'received_total' => '0.000000',
            'balance_due' => '0.000000',
            'revenue_total' => '0.000000',
            'stock_revenue_total' => '0.000000',
            'non_stock_revenue_total' => '0.000000',
        ];

        foreach ($lines as $line) {
            $netRevenue = $this->math->sub($line['line_subtotal'], $line['discount_amount']);
            $summary['subtotal'] = $this->math->add($summary['subtotal'], $line['line_subtotal']);
            $summary['discount_total'] = $this->math->add($summary['discount_total'], $line['discount_amount']);
            $summary['tax_total'] = $this->math->add($summary['tax_total'], $line['non_withholding_tax_amount']);
            $summary['withholding_total'] = $this->math->add($summary['withholding_total'], $line['withholding_amount']);
            $summary['grand_total'] = $this->math->add($summary['grand_total'], $line['line_total']);
            $summary['revenue_total'] = $this->math->add($summary['revenue_total'], $netRevenue);

            if ((bool) $line['is_stock']) {
                $summary['stock_revenue_total'] = $this->math->add($summary['stock_revenue_total'], $netRevenue);
            } else {
                $summary['non_stock_revenue_total'] = $this->math->add($summary['non_stock_revenue_total'], $netRevenue);
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
    private function resolvePayment(array $payload, bool $recordReceipt, array &$summary, int $tenantId, ?int $organizationUnitId, bool $lockRecords): array
    {
        if (! $recordReceipt) {
            return ['amount' => '0.000000', 'reference' => null, 'lines' => [], 'destination_accounts' => [], 'header_bank_account_id' => null];
        }

        $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $linePayloads = is_array($payment['lines'] ?? null) && $payment['lines'] !== []
            ? $payment['lines']
            : [[
                'amount' => $payment['amount'] ?? null,
                'payment_method_id' => $payment['payment_method_id'] ?? null,
                'destination_account_id' => $payment['destination_account_id'] ?? null,
                'reference' => $payment['reference'] ?? null,
                'instrument_number' => $payment['instrument_number'] ?? $payment['cheque_number'] ?? $payment['card_reference'] ?? null,
                'instrument_date' => $payment['instrument_date'] ?? $payment['cheque_date'] ?? null,
                'external_bank_name' => $payment['external_bank_name'] ?? null,
                'external_bank_branch' => $payment['external_bank_branch'] ?? null,
            ]];

        $lines = [];
        $destinationAccounts = [];
        $amount = '0.000000';
        $headerBankAccountId = null;

        foreach ($linePayloads as $line) {
            if (! is_array($line) || ($line['amount'] ?? null) === null) {
                throw new InvalidArgumentException('Receipt amount is required when recording customer receipt.');
            }

            $lineAmount = $this->math->normalize((string) $line['amount']);
            $accountId = $this->nullableInt($line['destination_account_id'] ?? null);
            if ($accountId === null) {
                throw new InvalidArgumentException('Receipt deposit account is required.');
            }

            $account = $this->paymentAccount($tenantId, $organizationUnitId, $accountId, $lockRecords);
            $methodId = $this->nullableInt($line['payment_method_id'] ?? null);
            $reference = $this->nullableString($line['reference'] ?? null);
            $instrumentNumber = $this->nullableString($line['instrument_number'] ?? null);
            if ($methodId !== null) {
                $method = $this->paymentMethod($tenantId, $organizationUnitId, $methodId, $lockRecords);
                if ((bool) $method->requires_bank_account && ! (bool) $account->is_bank_account) {
                    throw new InvalidArgumentException('Selected receipt method requires a bank account.');
                }
                if ((bool) $method->requires_reference && $reference === null && $instrumentNumber === null) {
                    throw new InvalidArgumentException('Selected receipt method requires a reference.');
                }
            }

            if ((bool) $account->is_bank_account && $headerBankAccountId === null) {
                $headerBankAccountId = (int) $account->getKey();
            }

            $lines[] = new PaymentLineData(
                amount: $lineAmount,
                paymentMethodId: $methodId,
                referenceNumber: $reference,
                internalBankAccountId: (bool) $account->is_bank_account ? (int) $account->getKey() : null,
                instrumentDirection: 'inbound',
                externalBankName: $this->nullableString($line['external_bank_name'] ?? null),
                externalBankBranch: $this->nullableString($line['external_bank_branch'] ?? null),
                instrumentNumber: $instrumentNumber,
                instrumentDate: $this->nullableString($line['instrument_date'] ?? null),
                metadata: ['destination_account_id' => (int) $account->getKey()],
            );
            $destinationAccounts[] = ['account' => $account, 'amount' => $lineAmount];
            $amount = $this->math->add($amount, $lineAmount);
        }

        if ($this->math->compare($amount, $summary['grand_total']) > 0) {
            throw new InvalidArgumentException('Receipt amount cannot exceed customer invoice balance.');
        }

        $summary['received_total'] = $amount;
        $summary['balance_due'] = $this->math->sub($summary['grand_total'], $amount);

        return [
            'amount' => $amount,
            'reference' => $this->nullableString($payment['reference'] ?? null),
            'lines' => $lines,
            'destination_accounts' => $destinationAccounts,
            'header_bank_account_id' => $headerBankAccountId,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function validateMode(array $lines, bool $createOrderOnly, bool $deliverItemsNow, bool $createInvoice, bool $recordReceipt): void
    {
        $hasStock = false;
        $hasNonStock = false;
        foreach ($lines as $line) {
            $hasStock = $hasStock || (bool) $line['is_stock'];
            $hasNonStock = $hasNonStock || ! (bool) $line['is_stock'];
        }

        if ($createOrderOnly && ($deliverItemsNow || $createInvoice || $recordReceipt)) {
            throw new InvalidArgumentException('Order-only mode cannot create delivery, invoice, or receipt documents.');
        }
        if ($recordReceipt && ! $createInvoice) {
            throw new InvalidArgumentException('Customer receipts require customer invoice creation.');
        }
        if (! $createOrderOnly && ! $deliverItemsNow && ! $createInvoice) {
            throw new InvalidArgumentException('Fast sales must create a sales order, delivery, or customer invoice.');
        }
        if (! $hasStock && $deliverItemsNow) {
            throw new InvalidArgumentException('Delivery requires at least one stock item.');
        }
        if ($hasStock && ! $createOrderOnly && ! $deliverItemsNow) {
            throw new InvalidArgumentException('Stock lines require delivery now unless you are creating a sales order only.');
        }
        if ($hasNonStock && $deliverItemsNow && ! $createInvoice) {
            throw new InvalidArgumentException('Delivery-only fast sales cannot include non-stock or service lines.');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function mode(array $lines, bool $createOrderOnly, bool $deliverItemsNow, bool $createInvoice, bool $recordReceipt): string
    {
        $hasStock = collect($lines)->contains(fn (array $line): bool => (bool) $line['is_stock']);
        $hasNonStock = collect($lines)->contains(fn (array $line): bool => ! (bool) $line['is_stock']);

        if ($createOrderOnly) {
            return 'order_only';
        }
        if ($deliverItemsNow && ! $createInvoice) {
            return 'delivery_only';
        }
        if ($hasNonStock && ! $hasStock) {
            return $recordReceipt ? 'direct_sale_paid' : 'direct_sale';
        }
        if ($recordReceipt) {
            return $hasNonStock ? 'mixed_cash_sale' : 'cash_sale';
        }

        return $hasNonStock ? 'mixed_credit_sale' : 'credit_sale';
    }

    /**
     * @param  array<string, string>  $summary
     */
    private function validateCredit(Customer $customer, array $summary, string $receivedTotal): void
    {
        $balanceDue = $this->math->sub($summary['grand_total'], $receivedTotal);
        if ($this->math->isZero($balanceDue)) {
            return;
        }

        if (! (bool) $customer->is_credit_allowed) {
            throw new InvalidArgumentException('Customer is not enabled for credit sales.');
        }

        $profile = $customer->creditProfile;
        if ($profile instanceof CustomerCreditProfile
            && (bool) $profile->is_active
            && ! (bool) $profile->allow_partial_payment
            && $this->math->compare($receivedTotal, '0.000000') > 0
            && $this->math->compare($receivedTotal, $summary['grand_total']) < 0) {
            throw new InvalidArgumentException('Customer credit profile does not allow partial receipts.');
        }

        $creditLimit = $profile instanceof CustomerCreditProfile && (bool) $profile->is_active
            ? $this->math->normalize((string) $profile->credit_limit)
            : $this->math->normalize((string) ($customer->credit_limit ?? '0.000000'));
        $allowOverCredit = $profile instanceof CustomerCreditProfile
            && (bool) $profile->is_active
            && (bool) $profile->allow_over_credit;
        if ($allowOverCredit || $this->math->isZero($creditLimit)) {
            return;
        }

        $outstanding = Invoice::query()
            ->where('tenant_id', (int) $customer->tenant_id)
            ->when(
                $customer->organization_unit_id === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $customer->organization_unit_id),
            )
            ->where('invoice_type', 'sales')
            ->where('direction', 'outbound')
            ->where('party_type', self::CUSTOMER_TYPE)
            ->where('party_id', (int) $customer->getKey())
            ->whereNotIn('status', ['cancelled', 'void'])
            ->sum('balance_due');
        $projected = $this->math->add((string) $outstanding, $balanceDue);
        if ($this->math->compare($projected, $creditLimit) > 0) {
            throw new InvalidArgumentException('Customer credit limit would be exceeded by this fast sale.');
        }
    }

    private function isStockItem(Item $item): bool
    {
        $type = $item->item_type instanceof ItemType ? $item->item_type : ItemType::from((string) $item->item_type);

        return (bool) $item->is_stockable && ! in_array($type, [ItemType::NonStock, ItemType::Service, ItemType::Labour], true);
    }

    private function invoiceLineType(Item $item): InvoiceLineType
    {
        $type = $item->item_type instanceof ItemType ? $item->item_type : ItemType::from((string) $item->item_type);

        return match ($type) {
            ItemType::Service => InvoiceLineType::Service,
            ItemType::Labour => InvoiceLineType::Labour,
            default => InvoiceLineType::Item,
        };
    }

    private function assertSalesUsage(Item $item, ?int $organizationUnitId): void
    {
        $rules = ItemUsageRule::query()
            ->where('tenant_id', (int) $item->tenant_id)
            ->where('item_id', (int) $item->getKey())
            ->where('is_enabled', true)
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                    $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
                }),
            )
            ->pluck('module_code');

        if ($rules->isNotEmpty() && ! $rules->contains('sales')) {
            throw new InvalidArgumentException('Sales item is not enabled for the sales module.');
        }
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function resolveUomId(int $tenantId, ?int $organizationUnitId, Item $item, array $line): int
    {
        $requested = $this->nullableInt($line['uom_id'] ?? null);
        if ($requested !== null) {
            return $requested;
        }

        foreach ([ItemUnitRole::Sales, ItemUnitRole::Service] as $role) {
            $unit = ItemUnit::query()
                ->where('tenant_id', $tenantId)
                ->where('item_id', $item->getKey())
                ->where('unit_role', $role->value)
                ->where('is_active', true)
                ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                    $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
                }))
                ->orderByDesc('is_default')
                ->first();
            if ($unit instanceof ItemUnit) {
                return (int) $unit->uom_id;
            }
        }

        $baseUomId = (int) ($item->base_uom_id ?: 0);
        if ($baseUomId < 1) {
            throw new InvalidArgumentException('Sales item requires a UOM.');
        }

        return $baseUomId;
    }

    private function resolveUnitPrice(
        int $tenantId,
        ?int $organizationUnitId,
        Item $item,
        ?int $variantId,
        ?int $currencyId,
        int $uomId,
        string $transactionDate,
    ): string {
        $type = $item->item_type instanceof ItemType ? $item->item_type : ItemType::from((string) $item->item_type);
        $priceTypes = match ($type) {
            ItemType::Service, ItemType::Labour => [ItemPriceType::Service, ItemPriceType::Sales, ItemPriceType::Standard],
            default => [ItemPriceType::Sales, ItemPriceType::Service, ItemPriceType::Standard],
        };

        foreach ($priceTypes as $priceType) {
            $price = ItemPrice::query()
                ->where('tenant_id', $tenantId)
                ->where('item_id', $item->getKey())
                ->where('price_type', $priceType->value)
                ->where('is_active', true)
                ->when($currencyId !== null, fn ($query) => $query->where('currency_id', $currencyId))
                ->when($variantId !== null, fn ($query) => $query->where(function ($scope) use ($variantId): void {
                    $scope->whereNull('item_variant_id')->orWhere('item_variant_id', $variantId);
                }))
                ->where(function ($query) use ($uomId): void {
                    $query->whereNull('uom_id')->orWhere('uom_id', $uomId);
                })
                ->where(function ($query) use ($transactionDate): void {
                    $query->whereNull('effective_from')->orWhere('effective_from', '<=', $transactionDate);
                })
                ->where(function ($query) use ($transactionDate): void {
                    $query->whereNull('effective_to')->orWhere('effective_to', '>=', $transactionDate);
                })
                ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                    $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
                }))
                ->when($variantId !== null, fn ($query) => $query->orderByRaw('case when item_variant_id = ? then 0 else 1 end', [$variantId]))
                ->orderByRaw('case when uom_id = ? then 0 else 1 end', [$uomId])
                ->latest('effective_from')
                ->first();
            if ($price instanceof ItemPrice) {
                return $this->math->normalize((string) $price->amount);
            }
        }

        return '0.000000';
    }

    private function customer(int $tenantId, ?int $organizationUnitId, int $customerId, bool $lockRecords): Customer
    {
        $customer = Customer::query()
            ->with(['creditProfile', 'defaultCurrency'])
            ->when($lockRecords, fn ($query) => $query->lockForUpdate())
            ->findOrFail($customerId);
        $this->validator->assertTenantOrg((int) $customer->tenant_id, $customer->organization_unit_id, $tenantId, $organizationUnitId);
        if ((string) $this->enumValue($customer->status) !== 'active') {
            throw new InvalidArgumentException('Sales customer must be active.');
        }

        return $customer;
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function currencyId(array $payload, Customer $customer, int $tenantId, ?int $organizationUnitId, bool $lockRecords): ?int
    {
        $currencyId = $this->nullableInt($payload['currency_id'] ?? null) ?? $this->nullableInt($customer->default_currency_id);
        if ($currencyId === null) {
            return null;
        }

        CurrencyModel::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($currencyId);
        $this->validator->currency($tenantId, $organizationUnitId, (int) $currencyId);

        return (int) $currencyId;
    }

    private function defaultTaxGroupId(Item $item): ?int
    {
        if ((bool) $item->is_tax_exempt) {
            return null;
        }

        return $this->nullableInt($item->sales_tax_group_id)
            ?? $this->nullableInt($item->default_tax_group_id);
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

        $direction = $method->direction_allowed instanceof PaymentMethodDirection
            ? $method->direction_allowed
            : PaymentMethodDirection::from((string) $method->direction_allowed);
        if (! in_array($direction, [PaymentMethodDirection::Inbound, PaymentMethodDirection::Both], true)) {
            throw new InvalidArgumentException('Payment method does not support inbound receipts.');
        }

        return $method;
    }

    private function paymentAccount(int $tenantId, ?int $organizationUnitId, int $accountId, bool $lockRecords): FinanceAccount
    {
        $account = FinanceAccount::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($accountId);
        if ((int) $account->tenant_id !== $tenantId || $account->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Receipt account belongs to a different scope.');
        }
        if (! (bool) $account->is_active || ! (bool) $account->is_posting_account) {
            throw new InvalidArgumentException('Receipt account must be active and postable.');
        }
        if (! (bool) $account->is_cash_account && ! (bool) $account->is_bank_account) {
            throw new InvalidArgumentException('Receipt account must be a cash or bank account.');
        }

        return $account;
    }

    private function lockStockBalance(
        int $tenantId,
        ?int $organizationUnitId,
        int $itemId,
        int $warehouseId,
        ?int $variantId,
        ?int $warehouseLocationId,
        bool $lockRecords,
    ): void {
        if (! $lockRecords) {
            return;
        }

        InventoryStockBalance::query()
            ->where('tenant_id', $tenantId)
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('item_variant_id', $variantId)
            ->where('warehouse_location_id', $warehouseLocationId)
            ->lockForUpdate()
            ->get();
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
            ->where('event', 'fast_sales.completed')
            ->where('auditable_type', 'fast_sales_reference')
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
            'customer_reference' => $resolved['customer_reference'],
            'mode' => $resolved['mode'],
            'options' => $resolved['options'],
            'summary' => $resolved['summary'],
            'customer' => $this->modelSummary($resolved['customer'], ['customer_number', 'code', 'name', 'display_name']),
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
        /** @var SalesOrder|null $order */
        $order = $documents['sales_order'];
        /** @var SalesDelivery|null $delivery */
        $delivery = $documents['goods_delivery'];
        /** @var Invoice|null $invoice */
        $invoice = $documents['customer_invoice'];
        /** @var Payment|null $payment */
        $payment = $documents['customer_receipt'];
        $financePostings = $documents['finance_postings'];
        $inventoryMovements = $delivery instanceof SalesDelivery
            ? $delivery->loadMissing('lines.inventoryMovement')->lines->pluck('inventoryMovement')->filter()->values()
            : collect();

        return [
            'customer_reference' => $resolved['customer_reference'],
            'mode' => $resolved['mode'],
            'options' => $resolved['options'],
            'summary' => $this->summaryWithReceived($resolved['summary'], $invoice, $payment),
            'customer' => $this->modelSummary($resolved['customer'], ['customer_number', 'code', 'name', 'display_name']),
            'lines' => array_map(fn (array $line): array => $this->linePreview($line), $resolved['lines']),
            'documents' => [
                'sales_order' => $order instanceof SalesOrder ? $this->salesOrderRef($order) : null,
                'goods_delivery' => $delivery instanceof SalesDelivery ? $this->deliveryRef($delivery) : null,
                'inventory_transaction' => $inventoryMovements->first() instanceof InventoryMovement ? $this->inventoryMovementRef($inventoryMovements->first()) : null,
                'inventory_transactions' => $inventoryMovements->map(fn (InventoryMovement $movement): array => $this->inventoryMovementRef($movement))->all(),
                'customer_invoice' => $invoice instanceof Invoice ? $this->invoiceRef($invoice) : null,
                'customer_receipt' => $payment instanceof Payment ? $this->paymentRef($payment) : null,
                'finance_posting' => isset($financePostings[0]) ? $this->financePostingRef($financePostings[0]) : null,
                'finance_postings' => array_map(fn (PostingResultData $posting): array => $this->financePostingRef($posting), $financePostings),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $summary
     * @return array<string, string>
     */
    private function summaryWithReceived(array $summary, ?Invoice $invoice, ?Payment $payment): array
    {
        if ($invoice instanceof Invoice) {
            $summary['received_total'] = (string) $invoice->paid_total;
            $summary['balance_due'] = (string) $invoice->balance_due;
        }
        if ($payment instanceof Payment) {
            $summary['received_total'] = (string) $payment->allocated_amount;
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
            'uom' => $this->uomSummary($line),
            'description' => $line['description'],
            'is_stock' => $line['is_stock'],
            'quantity' => $line['quantity'],
            'base_quantity' => $line['base_quantity'],
            'available_quantity' => $line['available_quantity'],
            'available_base_quantity' => $line['available_base_quantity'],
            'unit_price' => $line['unit_price'],
            'discount_amount' => $line['discount_amount'],
            'tax_amount' => $line['non_withholding_tax_amount'],
            'withholding_amount' => $line['withholding_amount'],
            'line_total' => $line['line_total'],
            'taxes' => $line['taxes'],
        ];
    }

    private function salesOrderRef(SalesOrder $order): array
    {
        return ['id' => (int) $order->getKey(), 'number' => (string) $order->sales_order_number, 'status' => $this->enumValue($order->status), 'url' => '/sales/orders/'.$order->getKey()];
    }

    private function deliveryRef(SalesDelivery $delivery): array
    {
        return ['id' => (int) $delivery->getKey(), 'number' => (string) $delivery->delivery_number, 'status' => $this->enumValue($delivery->status), 'url' => '/sales/deliveries?delivery_id='.$delivery->getKey()];
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
        return ['id' => (int) $movement->getKey(), 'number' => (string) $movement->movement_number, 'status' => $this->enumValue($movement->status), 'url' => '/inventory?movement_id='.$movement->getKey()];
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

    /**
     * @param  array<string, mixed>  $line
     */
    private function uomSummary(array $line): ?array
    {
        $uomId = $this->nullableInt($line['uom_id'] ?? null);
        if ($uomId === null) {
            return null;
        }

        $model = DB::table('unit_of_measures')
            ->where('id', $uomId)
            ->first(['id', 'code', 'name', 'symbol']);
        if ($model === null) {
            return null;
        }

        return [
            'id' => (int) $model->id,
            'code' => $model->code,
            'name' => $model->name,
            'symbol' => $model->symbol,
        ];
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
            event: 'fast_sales.completed',
            auditableType: 'fast_sales_reference',
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
                'customer_id' => (int) $resolved['customer']->getKey(),
                'customer_reference' => $resolved['customer_reference'],
                'transaction_date' => $resolved['transaction_date'],
            ],
            tags: ['sales', 'fast_sales'],
            occurredAt: now()->toDateTimeString(),
        ));

        if ($result->isFailure()) {
            throw new InvalidArgumentException('Fast sales audit log could not be written.');
        }
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function referenceHash(array $resolved): string
    {
        $reference = trim((string) $resolved['customer_reference']);
        if ($reference === '') {
            throw new InvalidArgumentException('Customer reference is required for fast sales submission.');
        }

        return hash('sha256', implode('|', [
            (string) $resolved['tenant_id'],
            (string) ($resolved['organization_unit_id'] ?? 'none'),
            (string) $resolved['customer']->getKey(),
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
        foreach (['subtotal', 'discount_total', 'tax_total', 'withholding_total', 'grand_total', 'received_total', 'balance_due', 'status', 'posting_status', 'approval_status', 'finance_account_id', 'receivable_account_id', 'revenue_account_id', 'inventory_account_id', 'cost_of_goods_sold_account_id', 'base_quantity', 'base_uom_quantity', 'available_stock', 'available_quantity'] as $key) {
            if (array_key_exists($key, $payload)) {
                throw new InvalidArgumentException('Fast sales totals, statuses, stock, quantities, and finance accounts are server controlled.');
            }
        }

        foreach (($payload['lines'] ?? []) as $line) {
            if (! is_array($line)) {
                continue;
            }
            foreach (['line_total', 'tax_amount', 'withholding_amount', 'base_quantity', 'base_uom_quantity', 'available_stock', 'available_quantity', 'finance_account_id', 'status', 'source_line_type', 'source_line_id'] as $key) {
                if (array_key_exists($key, $line)) {
                    throw new InvalidArgumentException('Fast sales line totals, tax, stock, statuses, base quantities, and source references are server controlled.');
                }
            }
        }
    }

    private function dueDate(string $transactionDate, string $paymentTerms, mixed $explicitDueDate): string
    {
        if ($explicitDueDate !== null && trim((string) $explicitDueDate) !== '') {
            return (string) $explicitDueDate;
        }
        if (preg_match('/(\d+)/', $paymentTerms, $matches) === 1) {
            return CarbonImmutable::parse($transactionDate)->addDays((int) $matches[1])->toDateString();
        }

        return $transactionDate;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function notes(array $payload): ?string
    {
        $notes = trim((string) ($payload['notes'] ?? ''));
        foreach ([
            'Customer reference' => trim((string) ($payload['customer_reference'] ?? '')),
            'Payment terms' => trim((string) ($payload['payment_terms'] ?? '')),
        ] as $label => $value) {
            if ($value !== '') {
                $notes = trim($notes."\n{$label}: {$value}");
            }
        }

        return $notes !== '' ? $notes : null;
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<SalesHeaderAdjustmentData>
     */
    private function orderAdjustments(array $resolved): array
    {
        return $this->math->isZero($resolved['summary']['withholding_total'])
            ? []
            : [new SalesHeaderAdjustmentData(
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

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<InvoiceAdjustmentData>
     */
    private function invoiceAdjustments(array $resolved): array
    {
        return $this->math->isZero($resolved['summary']['withholding_total'])
            ? []
            : [new InvoiceAdjustmentData(
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

    /**
     * @param  array<string, mixed>  $line
     */
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

    private function postingProfileAccount(
        int $tenantId,
        ?int $organizationUnitId,
        string $profileCode,
        string $lineKey,
    ): FinanceAccount {
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
                $query->where('direction_allowed', 'inbound')->orWhere('direction_allowed', 'both');
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
