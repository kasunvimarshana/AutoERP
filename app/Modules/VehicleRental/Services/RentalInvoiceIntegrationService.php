<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalChargeInvoiceStatus;
use Modules\VehicleRental\Enums\RentalChargeStatus;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalCharge;
use Modules\VehicleRental\Models\RentalInvoiceLink;

final class RentalInvoiceIntegrationService
{
    private const AGREEMENT_SOURCE = 'VehicleRentalAgreement';

    private const CHARGE_SOURCE = 'RentalCharge';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceCreationService $invoices,
    ) {}

    /**
     * @return Collection<int, RentalCharge>
     */
    public function billableCharges(RentalAgreement $agreement): Collection
    {
        return $agreement->charges()
            ->where('status', RentalChargeStatus::Approved->value)
            ->get()
            ->each(function (RentalCharge $charge) use ($agreement): void {
                $invoiced = $this->invoicedQuantity((int) $agreement->tenant_id, (int) $charge->getKey());
                $remaining = $this->math->sub((string) $charge->quantity, $invoiced);
                if ($this->math->isNegative($remaining)) {
                    $remaining = '0.000000';
                }
                $status = $this->math->isZero($remaining)
                    ? RentalChargeInvoiceStatus::Invoiced
                    : ($this->math->isZero($invoiced)
                        ? RentalChargeInvoiceStatus::NotInvoiced
                        : RentalChargeInvoiceStatus::PartiallyInvoiced);
                if ($charge->invoice_status !== $status) {
                    $charge->invoice_status = $status;
                    $charge->save();
                }
                $charge->setAttribute('invoiced_quantity', $invoiced);
                $charge->setAttribute('remaining_invoice_quantity', $remaining);
            });
    }

    /**
     * @param  array<int, string>  $chargeQuantities
     */
    public function preview(
        RentalAgreement $agreement,
        string $invoiceDate,
        array $chargeQuantities = [],
        ?string $dueDate = null,
    ): InvoiceCalculationResult {
        return $this->invoices->preview(
            $this->toInvoiceData($agreement, $invoiceDate, $chargeQuantities, $dueDate),
        );
    }

    /**
     * @param  array<int, string>  $chargeQuantities
     */
    public function create(
        RentalAgreement $agreement,
        string $invoiceDate,
        array $chargeQuantities = [],
        ?string $dueDate = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $notes = null,
        ?int $createdBy = null,
    ): Invoice {
        return DB::transaction(function () use (
            $agreement,
            $invoiceDate,
            $chargeQuantities,
            $dueDate,
            $currencyId,
            $exchangeRate,
            $notes,
            $createdBy,
        ): Invoice {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $chargeIds = $agreement->charges()
                ->where('status', RentalChargeStatus::Approved->value)
                ->lockForUpdate()
                ->pluck('id');
            if ($chargeIds->isNotEmpty()) {
                InvoiceSourceLine::query()
                    ->where('tenant_id', $agreement->tenant_id)
                    ->where('source_line_type', self::CHARGE_SOURCE)
                    ->whereIn('source_line_id', $chargeIds)
                    ->lockForUpdate()
                    ->get();
            }
            $data = $this->toInvoiceData(
                $agreement,
                $invoiceDate,
                $chargeQuantities,
                $dueDate,
                $currencyId,
                $exchangeRate,
                $notes,
                $createdBy,
            );
            $invoice = $this->invoices->create($data);

            foreach ($invoice->lines as $invoiceLine) {
                if ($invoiceLine->source_line_type !== self::CHARGE_SOURCE || $invoiceLine->source_line_id === null) {
                    continue;
                }
                $sourceLine = $invoice->sourceLines
                    ->firstWhere('source_line_id', $invoiceLine->source_line_id);
                RentalInvoiceLink::query()->create([
                    'tenant_id' => $agreement->tenant_id,
                    'organization_unit_id' => $agreement->organization_unit_id,
                    'agreement_id' => $agreement->getKey(),
                    'charge_id' => $invoiceLine->source_line_id,
                    'invoice_id' => $invoice->getKey(),
                    'invoice_line_id' => $invoiceLine->getKey(),
                    'invoiced_quantity' => (string) $sourceLine?->invoiced_quantity,
                    'invoiced_amount' => (string) $invoiceLine->line_total,
                    'status' => 'active',
                ]);
            }
            $this->billableCharges($agreement);

            return $invoice;
        });
    }

    /**
     * @param  array<int, string>  $chargeQuantities
     */
    private function toInvoiceData(
        RentalAgreement $agreement,
        string $invoiceDate,
        array $chargeQuantities,
        ?string $dueDate = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $notes = null,
        ?int $createdBy = null,
    ): CreateInvoiceData {
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Returned,
            RentalAgreementStatus::Completed,
        ], true)) {
            throw new InvalidArgumentException('Only returned or completed rental agreements can be invoiced.');
        }
        $agreement->loadMissing('rateSnapshot');
        $charges = $this->billableCharges($agreement);
        $validIds = $charges->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if (array_diff(array_keys($chargeQuantities), $validIds) !== []) {
            throw new InvalidArgumentException('Invoice selection contains a charge that is not billable for this agreement.');
        }

        $invoiceLines = [];
        $sourceLines = [];
        $selectedTotal = '0.000000';
        $selectedWithholding = '0.000000';
        $lineNumber = 1;
        $selectionProvided = $chargeQuantities !== [];
        foreach ($charges as $charge) {
            if ($selectionProvided && ! array_key_exists((int) $charge->getKey(), $chargeQuantities)) {
                continue;
            }
            $remaining = (string) $charge->getAttribute('remaining_invoice_quantity');
            if ($this->math->compare($remaining, '0.000000') <= 0) {
                continue;
            }
            $quantity = $this->math->normalize($chargeQuantities[(int) $charge->getKey()] ?? $remaining);
            if ($this->math->isZero($quantity)) {
                continue;
            }
            if ($this->math->compare($quantity, $remaining) > 0) {
                throw new InvalidArgumentException('Invoice quantity cannot exceed rental charge remaining quantity.');
            }
            $ratio = $this->math->div($quantity, (string) $charge->quantity, 12);
            $discount = $this->math->mul((string) $charge->discount_amount, $ratio);
            $tax = $this->math->mul((string) $charge->tax_amount, $ratio);
            $withholding = $this->math->mul((string) $charge->withholding_amount, $ratio);
            $lineTotal = $this->math->add(
                $this->math->sub($this->math->mul($quantity, (string) $charge->rate), $discount),
                $tax,
            );
            $selectedTotal = $this->math->add(
                $selectedTotal,
                $this->math->sub($lineTotal, $withholding),
            );
            $selectedWithholding = $this->math->add($selectedWithholding, $withholding);
            $invoiced = $this->invoicedQuantity((int) $agreement->tenant_id, (int) $charge->getKey());

            $invoiceLines[] = new InvoiceLineData(
                lineNumber: $lineNumber++,
                description: (string) $charge->description,
                quantity: $quantity,
                unitPrice: (string) $charge->rate,
                lineType: InvoiceLineType::Charge,
                discountAmount: $discount,
                taxAmount: $tax,
                lineTotal: $lineTotal,
                sourceLineType: self::CHARGE_SOURCE,
                sourceLineId: (int) $charge->getKey(),
                metadata: ['tax_group_id' => $agreement->rateSnapshot?->tax_profile_id],
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: (int) $agreement->tenant_id,
                sourceType: self::AGREEMENT_SOURCE,
                sourceId: (int) $agreement->getKey(),
                sourceLineType: self::CHARGE_SOURCE,
                sourceLineId: (int) $charge->getKey(),
                sourceQuantity: (string) $charge->quantity,
                invoicedQuantity: $quantity,
                sourceUnitPrice: (string) $charge->rate,
                sourceLineTotal: (string) $charge->total_amount,
                organizationUnitId: $agreement->organization_unit_id,
                previouslyInvoicedQuantity: $invoiced,
                invoicedLineTotal: $lineTotal,
            );
        }
        if ($invoiceLines === []) {
            throw new InvalidArgumentException('No approved rental charges remain to invoice.');
        }

        $allCharges = $agreement->charges()
            ->where('status', RentalChargeStatus::Approved->value)
            ->get();

        return new CreateInvoiceData(
            tenantId: (int) $agreement->tenant_id,
            invoiceType: InvoiceType::Rental,
            direction: $agreement->direction === RentalAgreementDirection::Outbound
                ? InvoiceDirection::Outbound
                : InvoiceDirection::Inbound,
            invoiceDate: $invoiceDate,
            organizationUnitId: $agreement->organization_unit_id,
            partyType: $agreement->party_type === RentalPartyType::Owner ? 'supplier' : $agreement->party_type->value,
            partyId: (int) $agreement->party_id,
            dueDate: $dueDate,
            currencyId: $currencyId ?? $agreement->currency_id ?? $agreement->rateSnapshot?->currency_id,
            exchangeRate: $exchangeRate,
            notes: $notes ?? 'Rental agreement '.$agreement->agreement_number,
            createdBy: $createdBy,
            lines: $invoiceLines,
            sources: [new InvoiceSourceData(
                tenantId: (int) $agreement->tenant_id,
                sourceType: self::AGREEMENT_SOURCE,
                sourceId: (int) $agreement->getKey(),
                organizationUnitId: $agreement->organization_unit_id,
                sourceDocumentNumber: (string) $agreement->agreement_number,
                sourceDocumentDate: $agreement->agreement_date->toDateString(),
                sourceSubtotal: $this->math->sum($allCharges->pluck('amount')->map(fn ($value) => (string) $value)->all()),
                sourceGrandTotal: $this->math->sum($allCharges->pluck('total_amount')->map(fn ($value) => (string) $value)->all()),
                invoicedAmount: $selectedTotal,
            )],
            sourceLines: $sourceLines,
            adjustments: $this->math->isZero($selectedWithholding) ? [] : [
                new InvoiceAdjustmentData(
                    name: 'Rental withholding',
                    adjustmentType: AdjustmentType::Withholding,
                    effect: AdjustmentEffect::Decrease,
                    amount: $selectedWithholding,
                    sourceType: self::AGREEMENT_SOURCE,
                    sourceId: (int) $agreement->getKey(),
                    allocationMethod: AllocationMethod::Manual,
                    isSystemGenerated: true,
                    description: 'Withholding retained from approved rental calculations.',
                ),
            ],
        );
    }

    private function invoicedQuantity(int $tenantId, int $chargeId): string
    {
        return $this->math->normalize((string) InvoiceSourceLine::query()
            ->where('tenant_id', $tenantId)
            ->where('source_line_type', self::CHARGE_SOURCE)
            ->where('source_line_id', $chargeId)
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ]))
            ->sum('invoiced_quantity'));
    }
}
