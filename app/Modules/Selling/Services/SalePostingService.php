<?php

declare(strict_types=1);

namespace Modules\Selling\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Idempotency\Enums\IdempotencyStatus;
use Modules\Idempotency\Services\IdempotencyService;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Services\StockMovementService;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Item\Services\ItemPriceResolutionService;
use Modules\Selling\Models\Sale;
use Modules\Selling\Models\SaleLine;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\Services\TaxCalculationService;
use Modules\Tenant\Models\TenantModel;

final class SalePostingService
{
    private const ZERO = '0.000000';

    private const IDEMPOTENCY_OPERATION = 'selling.sale.create';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $inventoryValidation,
        private readonly StockMovementService $stockMovements,
        private readonly ItemPriceResolutionService $prices,
        private readonly TaxCalculationService $taxes,
        private readonly InvoiceCreationService $invoices,
        private readonly InvoicePostingPlanFactory $postingPlans,
        private readonly IdempotencyService $idempotency,
    ) {}

    /** @param array<string, mixed> $payload */
    public function create(array $payload, string $idempotencyKey): Sale
    {
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Sale idempotency key is required.');
        }

        return DB::transaction(function () use ($payload, $idempotencyKey): Sale {
            $tenantId = (int) $payload['tenant_id'];
            $organizationUnitId = isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null;
            $customer = Customer::query()->lockForUpdate()->findOrFail((int) $payload['customer_id']);
            if ((int) $customer->tenant_id !== $tenantId || $customer->status !== CustomerStatus::Active) {
                throw new InvalidArgumentException('Select an active customer in this organization.');
            }
            if ($customer->organization_unit_id !== null
                && (int) $customer->organization_unit_id !== $organizationUnitId) {
                throw new InvalidArgumentException('Selected customer belongs to a different organization.');
            }

            $warehouse = $this->inventoryValidation->warehouse($tenantId, $organizationUnitId, (int) $payload['warehouse_id']);
            $locationId = isset($payload['warehouse_location_id']) ? (int) $payload['warehouse_location_id'] : null;
            $this->inventoryValidation->location($warehouse, $locationId);
            $referenceHash = hash('sha256', $idempotencyKey);
            $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
            $idempotency = $this->idempotency->acquire(
                $tenantId,
                $organizationUnitId,
                self::IDEMPOTENCY_OPERATION,
                $referenceHash,
                $payloadHash,
                $idempotencyKey,
                isset($payload['current_user_id']) ? (int) $payload['current_user_id'] : null,
            );

            if ($idempotency->status === IdempotencyStatus::Completed) {
                $saleId = $idempotency->document_ids['sale_id'] ?? null;
                if (! is_numeric($saleId)) {
                    throw new InvalidArgumentException('Completed sale request has no sale reference.');
                }

                return Sale::query()->with(['customer', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.uom', 'invoice'])
                    ->where('tenant_id', $tenantId)->findOrFail((int) $saleId);
            }
            if (! $idempotency->wasRecentlyCreated || $idempotency->status !== IdempotencyStatus::InProgress) {
                throw new InvalidArgumentException('Sale request is already being processed.');
            }

            $sale = Sale::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'customer_id' => $customer->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'warehouse_location_id' => $locationId,
                'sale_number' => 'SALE-'.Str::ulid(),
                'sale_date' => (string) $payload['sale_date'],
                'due_date' => $payload['due_date'] ?? null,
                'status' => 'draft',
                'notes' => $payload['notes'] ?? null,
                'created_by' => $payload['current_user_id'] ?? null,
            ]);

            $tenant = TenantModel::query()->findOrFail($tenantId);
            $currencyId = isset($payload['currency_id']) ? (int) $payload['currency_id'] : $tenant->base_currency_id;
            $invoiceLines = [];
            $taxLines = [];
            $sourceLines = [];
            $invoiceBase = self::ZERO;
            $itemSummaries = [];
            foreach (array_values($payload['lines']) as $index => $lineData) {
                $item = $this->inventoryValidation->item($tenantId, $organizationUnitId, (int) $lineData['item_id']);
                $this->inventoryValidation->assertStockable($item);
                $variantId = isset($lineData['item_variant_id']) ? (int) $lineData['item_variant_id'] : null;
                $variant = $this->inventoryValidation->variant($item, $variantId);
                $batchId = isset($lineData['batch_id']) ? (int) $lineData['batch_id'] : null;
                $batch = $this->inventoryValidation->batch($item, $batchId, $variantId);
                $serialId = isset($lineData['serial_number_id']) ? (int) $lineData['serial_number_id'] : null;
                $this->inventoryValidation->serial($item, $serialId, (string) $lineData['quantity'], $variantId, $batchId);
                $uomId = (int) ($lineData['uom_id'] ?? $item->base_uom_id);
                if ($uomId < 1) {
                    throw new InvalidArgumentException('Each sale item must have a valid unit of measure.');
                }
                $quantity = $this->math->normalize((string) $lineData['quantity']);
                $price = $this->prices->resolvePrice($item, ItemPriceResolutionService::CONTEXT_SALES, $uomId, $organizationUnitId, $currencyId, (string) $payload['sale_date'], $variantId);
                if (! $price->hasAmount()) {
                    throw new InvalidArgumentException('A current sales price is not configured for '.$item->name.'.');
                }
                $unitPrice = (string) $price->amount;
                $description = (string) $item->name.($variant?->name ? ' - '.$variant->name : '');
                $saleLine = SaleLine::query()->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'sale_id' => $sale->getKey(),
                    'line_number' => $index + 1,
                    'item_id' => $item->getKey(),
                    'item_variant_id' => $variantId,
                    'uom_id' => $uomId,
                    'batch_id' => $batchId,
                    'serial_number_id' => $serialId,
                    'quantity' => $quantity,
                    'base_quantity' => self::ZERO,
                    'unit_price' => $unitPrice,
                    'unit_cost_snapshot' => self::ZERO,
                    'line_total' => $this->math->mul($quantity, $unitPrice),
                    'description' => $description,
                ]);
                $movement = $this->stockMovements->record(new StockMovementData(
                    tenantId: $tenantId,
                    organizationUnitId: $organizationUnitId,
                    movementDate: (string) $payload['sale_date'],
                    movementType: InventoryMovementType::Issue,
                    direction: InventoryDirection::Out,
                    itemId: (int) $item->getKey(),
                    itemVariantId: $variantId,
                    warehouseId: (int) $warehouse->getKey(),
                    warehouseLocationId: $locationId,
                    batchId: $batchId,
                    serialNumberId: $serialId,
                    quantity: $quantity,
                    unitCost: self::ZERO,
                    sourceType: 'sale',
                    sourceId: (int) $sale->getKey(),
                    sourceLineType: 'sale_line',
                    sourceLineId: (int) $saleLine->getKey(),
                    description: 'Stock issue for '.$sale->sale_number,
                    createdBy: isset($payload['current_user_id']) ? (int) $payload['current_user_id'] : null,
                    uomId: $uomId,
                ), isset($payload['current_user_id']) ? (int) $payload['current_user_id'] : null);
                $saleLine->forceFill([
                    'base_quantity' => $movement->quantity,
                    'unit_cost_snapshot' => $movement->unit_cost,
                    'inventory_movement_id' => $movement->getKey(),
                ])->save();

                $taxLines[] = new TaxCalculationLineData(
                    lineNumber: $index + 1,
                    quantity: $quantity,
                    unitPrice: $unitPrice,
                    itemId: (int) $item->getKey(),
                    taxGroupId: $item->sales_tax_group_id === null ? null : (int) $item->sales_tax_group_id,
                );
                $invoiceBase = $this->math->add($invoiceBase, $this->math->mul($quantity, $unitPrice));
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $tenantId,
                    organizationUnitId: $organizationUnitId,
                    sourceType: 'sale',
                    sourceId: (int) $sale->getKey(),
                    sourceLineType: 'sale_line',
                    sourceLineId: (int) $saleLine->getKey(),
                    sourceQuantity: $quantity,
                    invoicedQuantity: $quantity,
                    sourceUnitPrice: $unitPrice,
                    sourceLineTotal: (string) $saleLine->line_total,
                    invoicedLineTotal: (string) $saleLine->line_total,
                );
                $itemSummaries[] = ['line' => $saleLine, 'item' => $item];
            }

            $taxCalculation = $this->taxes->calculate(new TaxCalculationData(
                tenantId: $tenantId,
                organizationUnitId: $organizationUnitId,
                customerId: (int) $customer->getKey(),
                documentType: 'invoice_outbound_sales',
                documentDate: (string) $payload['sale_date'],
                lines: $taxLines,
            ));
            $taxResults = collect($taxCalculation->lineResults)->keyBy('lineNumber');
            foreach ($itemSummaries as $summary) {
                /** @var SaleLine $saleLine */
                $saleLine = $summary['line'];
                $taxResult = $taxResults->get((int) $saleLine->line_number);
                $taxSnapshots = array_map(static fn ($tax): array => [
                    'tax_id' => $tax->taxId,
                    'tax_code' => $tax->taxCode,
                    'tax_name' => $tax->taxName,
                    'tax_type' => $tax->taxType,
                    'calculation_method' => $tax->calculationMethod,
                    'rate' => $tax->rate,
                    'sequence' => $tax->sequence,
                    'taxable_amount' => $tax->taxableAmount,
                    'tax_amount' => $tax->taxAmount,
                    'is_withholding' => $tax->isWithholding,
                ], $taxResult->taxes);
                $invoiceLines[] = new InvoiceLineData(
                    lineNumber: (int) $saleLine->line_number,
                    description: (string) $saleLine->description,
                    quantity: (string) $saleLine->quantity,
                    unitPrice: (string) $saleLine->unit_price,
                    lineType: InvoiceLineType::Item,
                    itemId: (int) $saleLine->item_id,
                    uomId: (int) $saleLine->uom_id,
                    taxAmount: $taxResult->taxAmount,
                    lineTotal: $this->math->add($taxResult->totalAmount, $taxResult->withholdingAmount),
                    sourceLineType: 'sale_line',
                    sourceLineId: (int) $saleLine->getKey(),
                    metadata: [
                        InvoiceTaxMetadata::TAX_GROUP_ID => $summary['item']->sales_tax_group_id,
                        InvoiceTaxMetadata::TAXES => $taxSnapshots,
                        InvoiceTaxMetadata::WITHHOLDING_AMOUNT => $taxResult->withholdingAmount,
                    ],
                );
            }
            $invoice = $this->invoices->create(new CreateInvoiceData(
                tenantId: $tenantId,
                organizationUnitId: $organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: (string) $payload['sale_date'],
                partyType: 'customer',
                partyId: (int) $customer->getKey(),
                dueDate: $payload['due_date'] ?? null,
                currencyId: $currencyId === null ? null : (int) $currencyId,
                exchangeRate: isset($payload['exchange_rate']) ? (string) $payload['exchange_rate'] : '1.000000',
                status: InvoiceStatus::Posted,
                notes: $payload['notes'] ?? null,
                createdBy: isset($payload['current_user_id']) ? (int) $payload['current_user_id'] : null,
                lines: $invoiceLines,
                sources: [new InvoiceSourceData(
                    tenantId: $tenantId,
                    organizationUnitId: $organizationUnitId,
                    sourceType: 'sale',
                    sourceId: (int) $sale->getKey(),
                    sourceDocumentNumber: (string) $sale->sale_number,
                    sourceDocumentDate: (string) $sale->sale_date->toDateString(),
                    sourceSubtotal: $invoiceBase,
                    sourceGrandTotal: $taxCalculation->totalAmount,
                    invoicedAmount: $taxCalculation->totalAmount,
                )],
                sourceLines: $sourceLines,
                taxCalculation: $taxCalculation,
                postingPlan: $this->postingPlans->outbound(
                    FinancePostingProfileCode::SalesInvoice,
                    (string) $payload['sale_date'],
                    FinanceAccountRoleCode::Revenue,
                    $taxCalculation->taxableAmount,
                    $taxCalculation->taxAmount,
                    $taxCalculation->withholdingAmount,
                    'Sales invoice '.$sale->sale_number,
                ),
                paymentMode: isset($payload['due_date']) ? 'Credit' : null,
            ));
            $sale->forceFill([
                'invoice_id' => $invoice->getKey(),
                'status' => 'posted',
                'posted_at' => now(),
            ])->save();
            $this->idempotency->complete($idempotency, ['sale_id' => (int) $sale->getKey(), 'invoice_id' => (int) $invoice->getKey()], ['sale_id' => (int) $sale->getKey(), 'invoice_id' => (int) $invoice->getKey()]);

            return $sale->refresh()->load(['customer', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'invoice.balance']);
        }, 3);
    }
}
