<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Idempotency\Enums\IdempotencyStatus;
use Modules\Idempotency\Services\IdempotencyService;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\ManualInvoiceData;
use Modules\Invoice\DTOs\ManualInvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;

final class ManualInvoiceService
{
    private const IDEMPOTENCY_OPERATION = 'invoice.manual.create';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly TaxedSourceInvoiceFactory $sourceInvoices,
        private readonly InvoiceCreationService $invoices,
        private readonly IdempotencyService $idempotency,
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
        $source = new CreateInvoiceData(
            tenantId: $data->tenantId, invoiceType: InvoiceType::Manual, direction: $data->direction,
            invoiceDate: $data->invoiceDate, organizationUnitId: $data->organizationUnitId,
            partyType: InvoicePartyType::forDirection($data->direction)->value, partyId: $this->partyId($data),
            dueDate: $data->dueDate, currencyId: $data->currencyId, exchangeRate: $this->math->normalize($data->exchangeRate),
            notes: $this->nullableTrimmed($data->notes), createdBy: $data->createdBy, lines: $this->invoiceLines($data),
            supplyDate: $this->nullableTrimmed($data->supplyDate), supplyPeriodStart: $this->nullableTrimmed($data->supplyPeriodStart),
            supplyPeriodEnd: $this->nullableTrimmed($data->supplyPeriodEnd), placeOfSupply: $this->nullableTrimmed($data->placeOfSupply),
            paymentMode: $this->nullableTrimmed($data->paymentMode), paymentTerms: $this->nullableTrimmed($data->paymentTerms),
        );

        return $this->sourceInvoices->prepare($source,
            $data->direction === InvoiceDirection::Outbound ? FinancePostingProfileCode::SalesInvoice : FinancePostingProfileCode::PurchaseInvoice,
            $data->direction === InvoiceDirection::Outbound ? FinanceAccountRoleCode::Revenue : FinanceAccountRoleCode::Expense);
    }

    /** @return list<InvoiceLineData> */
    private function invoiceLines(ManualInvoiceData $data): array
    {
        $lines = [];
        foreach (array_values($data->lines) as $index => $line) {
            if (! $line instanceof ManualInvoiceLineData) {
                throw new InvalidArgumentException('Manual invoice lines must be ManualInvoiceLineData instances.');
            }
            if ($line->lineType === InvoiceLineType::Item && $line->itemId === null) {
                throw new InvalidArgumentException('Manual invoice item lines require an item reference.');
            }
            $lines[] = new InvoiceLineData(lineNumber: $index + 1, description: trim($line->description),
                quantity: $this->math->normalize($line->quantity), unitPrice: $this->math->normalize($line->unitPrice),
                lineType: $line->lineType, itemId: $line->itemId, uomId: $line->uomId,
                discountAmount: $this->math->normalize($line->discountAmount), chargeAmount: $this->math->normalize($line->chargeAmount),
                metadata: [InvoiceTaxMetadata::TAX_GROUP_ID => $line->taxGroupId ?? $data->documentTaxGroupId]);
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

    private function invoiceInScope(ManualInvoiceData $data, int $invoiceId): Invoice
    {
        return Invoice::query()
            ->where('tenant_id', $data->tenantId)
            ->where('organization_unit_id', $data->organizationUnitId)
            ->with(['lines', 'adjustments', 'balance', 'postingPlan', 'documentSnapshot'])
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
                'supply_date' => $this->nullableTrimmed($data->supplyDate),
                'supply_period_start' => $this->nullableTrimmed($data->supplyPeriodStart),
                'supply_period_end' => $this->nullableTrimmed($data->supplyPeriodEnd),
                'place_of_supply' => $this->nullableTrimmed($data->placeOfSupply),
                'payment_mode' => $this->nullableTrimmed($data->paymentMode),
                'payment_terms' => $this->nullableTrimmed($data->paymentTerms),
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
