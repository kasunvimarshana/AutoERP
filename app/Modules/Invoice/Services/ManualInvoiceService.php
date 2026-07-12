<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Idempotency\Enums\IdempotencyStatus;
use Modules\Idempotency\Services\IdempotencyService;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\ManualInvoiceData;
use Modules\Invoice\DTOs\ManualInvoiceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\DTOs\TaxLineCalculationResult;
use Modules\Tax\Services\TaxCalculationService;

final class ManualInvoiceService
{
    private const IDEMPOTENCY_OPERATION = 'invoice.manual.create';

    private const TAX_DOCUMENT_OUTBOUND = 'invoice_outbound_manual';

    private const TAX_DOCUMENT_INBOUND = 'invoice_inbound_manual';

    private const CALCULATION_TYPE_FIXED = 'fixed';

    private const WITHHOLDING_ADJUSTMENT_NAME = 'Tax withholding';

    private const WITHHOLDING_ADJUSTMENT_DESCRIPTION = 'Withholding calculated by the Tax module.';

    private const ZERO = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly TaxCalculationService $taxes,
        private readonly InvoiceCreationService $invoices,
        private readonly IdempotencyService $idempotency,
        private readonly InvoicePostingPlanFactory $postingPlans,
    ) {}

    public function preview(ManualInvoiceData $data): InvoiceCalculationResult
    {
        return $this->invoices->preview($this->prepare($data));
    }

    public function create(ManualInvoiceData $data, string $idempotencyKey): Invoice
    {
        $idempotencyKey = trim($idempotencyKey);
        if ($idempotencyKey === '') {
            throw new InvalidArgumentException('Manual invoice idempotency key is required.');
        }

        return DB::transaction(function () use ($data, $idempotencyKey): Invoice {
            $referenceHash = hash('sha256', $idempotencyKey);
            $payloadHash = $this->payloadHash($data);
            $record = $this->idempotency->acquire(
                $data->tenantId,
                $data->organizationUnitId,
                self::IDEMPOTENCY_OPERATION,
                $referenceHash,
                $payloadHash,
                $idempotencyKey,
                $data->createdBy,
            );

            if ($record->status === IdempotencyStatus::Completed) {
                $invoiceId = $record->document_ids['invoice_id'] ?? $record->result['invoice_id'] ?? null;
                if (! is_numeric($invoiceId) || (int) $invoiceId < 1) {
                    throw new LogicException('Completed manual-invoice idempotency record has no invoice identifier.');
                }

                return $this->invoiceInScope($data, (int) $invoiceId);
            }

            if (! $record->wasRecentlyCreated) {
                throw new InvalidArgumentException('Manual invoice request is already in progress.');
            }

            $invoice = $this->invoices->create($this->prepare($data));
            $result = ['invoice_id' => (int) $invoice->getKey()];
            $this->idempotency->complete($record, $result, $result);

            return $invoice;
        });
    }

    private function prepare(ManualInvoiceData $data): CreateInvoiceData
    {
        $partyType = InvoicePartyType::forDirection($data->direction);
        $partyId = $this->partyId($data);
        $taxCalculation = $this->taxes->calculate(new TaxCalculationData(
            tenantId: $data->tenantId,
            documentType: $this->taxDocumentType($data->direction),
            documentDate: $data->invoiceDate,
            organizationUnitId: $data->organizationUnitId,
            customerId: $data->customerId,
            supplierId: $data->supplierId,
            documentTaxGroupId: $data->documentTaxGroupId,
            lines: $this->taxLines($data),
        ));

        $taxResults = [];
        foreach ($taxCalculation->lineResults as $result) {
            $taxResults[$result->lineNumber] = $result;
        }

        $lines = [];
        $baseAmount = self::ZERO;
        foreach (array_values($data->lines) as $index => $line) {
            if (! $line instanceof ManualInvoiceLineData) {
                throw new InvalidArgumentException('Manual invoice lines must be ManualInvoiceLineData instances.');
            }

            $lineNumber = $index + 1;
            $taxResult = $taxResults[$lineNumber] ?? null;
            if (! $taxResult instanceof TaxLineCalculationResult) {
                throw new LogicException("Tax calculation result is missing for invoice line [{$lineNumber}].");
            }

            $quantity = $this->math->normalize($line->quantity);
            $unitPrice = $this->math->normalize($line->unitPrice);
            $discount = $this->math->normalize($line->discountAmount);
            $charge = $this->math->normalize($line->chargeAmount);
            $baseAmount = $this->math->add(
                $baseAmount,
                $this->math->add(
                    $this->math->sub($this->math->mul($quantity, $unitPrice), $discount),
                    $charge,
                ),
            );

            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: trim($line->description),
                quantity: $quantity,
                unitPrice: $unitPrice,
                lineType: $line->lineType,
                itemId: $line->itemId,
                uomId: $line->uomId,
                discountAmount: $discount,
                taxAmount: $taxResult->taxAmount,
                chargeAmount: $charge,
                lineTotal: $this->math->add($taxResult->totalAmount, $taxResult->withholdingAmount),
                metadata: [
                    InvoiceTaxMetadata::TAX_GROUP_ID => $line->taxGroupId,
                    InvoiceTaxMetadata::TAXES => array_map($this->taxSnapshot(...), $taxResult->taxes),
                    InvoiceTaxMetadata::WITHHOLDING_AMOUNT => $taxResult->withholdingAmount,
                ],
            );
        }

        $adjustments = [];
        if (! $this->math->isZero($taxCalculation->withholdingAmount)) {
            $adjustments[] = new InvoiceAdjustmentData(
                name: self::WITHHOLDING_ADJUSTMENT_NAME,
                adjustmentType: AdjustmentType::Withholding,
                effect: AdjustmentEffect::Decrease,
                amount: $taxCalculation->withholdingAmount,
                calculationType: self::CALCULATION_TYPE_FIXED,
                allocationMethod: AllocationMethod::Manual,
                isSystemGenerated: true,
                description: self::WITHHOLDING_ADJUSTMENT_DESCRIPTION,
            );
        }

        $postingPlan = match ($data->direction) {
            InvoiceDirection::Outbound => $this->postingPlans->outbound(
                FinancePostingProfileCode::SalesInvoice,
                $data->invoiceDate,
                FinanceAccountRoleCode::Revenue,
                $baseAmount,
                $taxCalculation->taxAmount,
                $taxCalculation->withholdingAmount,
                'Manual sales invoice',
            ),
            InvoiceDirection::Inbound => $this->postingPlans->inbound(
                FinancePostingProfileCode::PurchaseInvoice,
                $data->invoiceDate,
                FinanceAccountRoleCode::Expense,
                $baseAmount,
                $taxCalculation->taxAmount,
                $taxCalculation->withholdingAmount,
                'Manual purchase invoice',
            ),
        };

        return new CreateInvoiceData(
            tenantId: $data->tenantId,
            invoiceType: InvoiceType::Manual,
            direction: $data->direction,
            invoiceDate: $data->invoiceDate,
            organizationUnitId: $data->organizationUnitId,
            partyType: $partyType->value,
            partyId: $partyId,
            dueDate: $data->dueDate,
            currencyId: $data->currencyId,
            exchangeRate: $this->math->normalize($data->exchangeRate),
            notes: $this->nullableTrimmed($data->notes),
            createdBy: $data->createdBy,
            lines: $lines,
            adjustments: $adjustments,
            taxCalculation: $taxCalculation,
            postingPlan: $postingPlan,
        );
    }

    /** @return list<TaxCalculationLineData> */
    private function taxLines(ManualInvoiceData $data): array
    {
        $lines = [];
        foreach (array_values($data->lines) as $index => $line) {
            if (! $line instanceof ManualInvoiceLineData) {
                throw new InvalidArgumentException('Manual invoice lines must be ManualInvoiceLineData instances.');
            }

            if ($line->lineType === InvoiceLineType::Item && $line->itemId === null) {
                throw new InvalidArgumentException('Manual invoice item lines require an item reference.');
            }

            $lines[] = new TaxCalculationLineData(
                lineNumber: $index + 1,
                quantity: $this->math->normalize($line->quantity),
                unitPrice: $this->math->normalize($line->unitPrice),
                itemId: $line->itemId,
                taxGroupId: $line->taxGroupId,
                discountBeforeTax: $this->math->normalize($line->discountAmount),
                chargeAfterTax: $this->math->normalize($line->chargeAmount),
            );
        }

        if ($lines === []) {
            throw new InvalidArgumentException('Manual invoice requires at least one line.');
        }

        return $lines;
    }

    private function partyId(ManualInvoiceData $data): int
    {
        $partyId = match ($data->direction) {
            InvoiceDirection::Outbound => $data->customerId,
            InvoiceDirection::Inbound => $data->supplierId,
        };

        if ($partyId === null || $partyId < 1) {
            throw new InvalidArgumentException('Manual invoice requires the party owned by its direction.');
        }

        return $partyId;
    }

    private function taxDocumentType(InvoiceDirection $direction): string
    {
        return match ($direction) {
            InvoiceDirection::Outbound => self::TAX_DOCUMENT_OUTBOUND,
            InvoiceDirection::Inbound => self::TAX_DOCUMENT_INBOUND,
        };
    }

    /** @return array<string, int|string|bool> */
    private function taxSnapshot(TaxAmountData $tax): array
    {
        return [
            'tax_id' => $tax->taxId,
            'tax_code' => $tax->taxCode,
            'tax_name' => $tax->taxName,
            'tax_type' => $tax->taxType,
            'calculation_method' => $tax->calculationMethod,
            'rate' => $tax->rate,
            'sequence' => $tax->sequence,
            'taxable_amount' => $tax->taxableAmount,
            'tax_amount' => $tax->taxAmount,
            'total_after_tax' => $tax->totalAfterTax,
            'is_withholding' => $tax->isWithholding,
            'recoverable' => $tax->recoverable,
            'payable' => $tax->payable,
            'receivable' => $tax->receivable,
        ];
    }

    private function invoiceInScope(ManualInvoiceData $data, int $invoiceId): Invoice
    {
        return Invoice::query()
            ->where('tenant_id', $data->tenantId)
            ->where('organization_unit_id', $data->organizationUnitId)
            ->with(['lines', 'adjustments', 'balance', 'postingPlan'])
            ->findOrFail($invoiceId);
    }

    private function nullableTrimmed(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function payloadHash(ManualInvoiceData $data): string
    {
        try {
            return hash('sha256', json_encode([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'direction' => $data->direction->value,
                'invoice_date' => $data->invoiceDate,
                'customer_id' => $data->customerId,
                'supplier_id' => $data->supplierId,
                'due_date' => $data->dueDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'document_tax_group_id' => $data->documentTaxGroupId,
                'notes' => $this->nullableTrimmed($data->notes),
                'created_by' => $data->createdBy,
                'lines' => array_map(fn (ManualInvoiceLineData $line): array => [
                    'description' => trim($line->description),
                    'quantity' => $this->math->normalize($line->quantity),
                    'unit_price' => $this->math->normalize($line->unitPrice),
                    'line_type' => $line->lineType->value,
                    'item_id' => $line->itemId,
                    'uom_id' => $line->uomId,
                    'tax_group_id' => $line->taxGroupId,
                    'discount_amount' => $this->math->normalize($line->discountAmount),
                    'charge_amount' => $this->math->normalize($line->chargeAmount),
                ], $data->lines),
            ], JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new LogicException('Unable to normalize manual invoice idempotency payload.', previous: $exception);
        }
    }
}
