<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
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
use Modules\Invoice\Models\InvoiceAdjustmentAllocation;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\VehicleService\Enums\VehicleServiceDiscountRevisionAction;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceInvoiceSourceMapper
{
    private const ZERO = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoicePostingPlanFactory $postingPlans,
    ) {}

    /** @return Collection<int, VehicleServiceJobLine> */
    public function billableLines(VehicleServiceJob $job): Collection
    {
        return $job->lines()
            ->where('is_billable', true)
            ->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
            ->with(['item', 'variant', 'uom'])
            ->get()
            ->each(function (VehicleServiceJobLine $line) use ($job): void {
                $invoiced = $this->invoicedQuantity((int) $job->tenant_id, (int) $line->getKey());
                $remaining = $this->math->sub((string) $line->quantity, $invoiced);
                if ($this->math->isNegative($remaining)) {
                    $remaining = self::ZERO;
                }

                $line->setAttribute('invoiced_quantity', $invoiced);
                $line->setAttribute('remaining_billable_quantity', $remaining);
                $line->setAttribute('invoice_state', $this->math->isZero($remaining)
                    ? 'invoiced'
                    : ($this->math->isZero($invoiced) ? 'uninvoiced' : 'partially_invoiced'));
            });
    }

    /** @param array<int, string> $lineQuantities */
    public function toInvoiceData(
        VehicleServiceJob $job,
        string $invoiceDate,
        array $lineQuantities,
        ?string $dueDate = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $notes = null,
        ?int $createdBy = null,
    ): CreateInvoiceData {
        $this->assertInvoiceable($job);

        $lines = $job->lines()
            ->where('is_billable', true)
            ->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
            ->with('item')
            ->get();
        $invoiceLines = [];
        $sourceLines = [];
        $selectedTotal = self::ZERO;
        $baseAmount = self::ZERO;
        $taxAmount = self::ZERO;
        $selectedDiscountBase = self::ZERO;
        $allocatedJobDiscount = self::ZERO;
        $allRemainingSelected = true;
        $lineNumber = 1;
        $selectionProvided = $lineQuantities !== [];
        $validLineIds = $lines->pluck('id')->map(fn ($id): int => (int) $id)->all();

        if (array_diff(array_keys($lineQuantities), $validLineIds) !== []) {
            throw new InvalidArgumentException(
                'Invoice selection contains a line that is not billable for this service job.',
            );
        }

        foreach ($lines as $line) {
            $previouslyInvoiced = $this->invoicedQuantity(
                (int) $job->tenant_id,
                (int) $line->getKey(),
            );
            $remaining = $this->math->sub((string) $line->quantity, $previouslyInvoiced);
            if ($this->math->compare($remaining, self::ZERO) <= 0) {
                continue;
            }

            if ($selectionProvided && ! array_key_exists((int) $line->getKey(), $lineQuantities)) {
                $allRemainingSelected = false;

                continue;
            }

            $quantity = $this->math->normalize(
                $lineQuantities[(int) $line->getKey()] ?? $remaining,
            );
            if ($this->math->isZero($quantity)) {
                $allRemainingSelected = false;

                continue;
            }
            if ($this->math->compare($quantity, $remaining) > 0) {
                throw new InvalidArgumentException(
                    'Invoice quantity cannot exceed service job line remaining quantity.',
                );
            }
            if ($this->math->compare($quantity, $remaining) < 0) {
                $allRemainingSelected = false;
            }

            $ratio = $this->math->div($quantity, (string) $line->quantity, 12);
            $discount = $this->math->mul((string) $line->discount_amount, $ratio);
            $tax = $this->math->mul((string) $line->tax_amount, $ratio);
            $charge = $this->math->mul((string) $line->charge_amount, $ratio);
            $selectedDiscountBase = $this->math->add(
                $selectedDiscountBase,
                $this->math->sub(
                    $this->math->mul($quantity, (string) $line->unit_price),
                    $discount,
                ),
            );
            $lineBase = $this->math->add(
                $this->math->sub(
                    $this->math->mul($quantity, (string) $line->unit_price),
                    $discount,
                ),
                $charge,
            );
            $lineTotal = $this->math->add($lineBase, $tax);
            $selectedTotal = $this->math->add($selectedTotal, $lineTotal);
            $baseAmount = $this->math->add($baseAmount, $lineBase);
            $taxAmount = $this->math->add($taxAmount, $tax);

            $invoiceLines[] = new InvoiceLineData(
                lineNumber: $lineNumber++,
                description: (string) $line->description,
                quantity: $quantity,
                unitPrice: (string) $line->unit_price,
                lineType: $this->invoiceLineType($line->line_source_type),
                itemId: $line->item_id,
                uomId: $line->uom_id,
                discountAmount: $discount,
                taxAmount: $tax,
                chargeAmount: $charge,
                lineTotal: $lineTotal,
                sourceLineType: 'vehicle_service_job_line',
                sourceLineId: (int) $line->getKey(),
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: (int) $job->tenant_id,
                sourceType: 'vehicle_service_job',
                sourceId: (int) $job->getKey(),
                sourceLineType: 'vehicle_service_job_line',
                sourceLineId: (int) $line->getKey(),
                sourceQuantity: (string) $line->quantity,
                invoicedQuantity: $quantity,
                sourceUnitPrice: (string) $line->unit_price,
                sourceLineTotal: (string) $line->line_total,
                organizationUnitId: $job->organization_unit_id,
                previouslyInvoicedQuantity: $previouslyInvoiced,
                invoicedLineTotal: $lineTotal,
            );
        }

        if ($invoiceLines === []) {
            throw new InvalidArgumentException('No billable service job lines remain to invoice.');
        }

        $adjustments = [];
        $discountRevision = $job->discountRevisions()->first();
        if ($discountRevision !== null
            && $discountRevision->action === VehicleServiceDiscountRevisionAction::Set
            && $this->math->compare((string) $job->job_discount_amount, self::ZERO) > 0) {
            $previouslyAllocated = $this->previouslyAllocatedDiscount(
                (int) $job->tenant_id,
                (int) $discountRevision->getKey(),
            );
            $remainingDiscount = $this->math->sub((string) $job->job_discount_amount, $previouslyAllocated);
            $allocatedJobDiscount = $allRemainingSelected
                ? $remainingDiscount
                : $this->math->mul(
                    (string) $job->job_discount_amount,
                    $this->math->div($selectedDiscountBase, (string) $job->job_discount_base, 12),
                );
            if ($this->math->compare($allocatedJobDiscount, $remainingDiscount) > 0) {
                $allocatedJobDiscount = $remainingDiscount;
            }

            $adjustments[] = new InvoiceAdjustmentData(
                name: 'Vehicle service job discount',
                adjustmentType: AdjustmentType::Discount,
                effect: AdjustmentEffect::Decrease,
                amount: $allocatedJobDiscount,
                sourceAdjustmentType: 'vehicle_service_job_discount',
                sourceAdjustmentId: (int) $discountRevision->getKey(),
                sourceType: 'vehicle_service_job',
                sourceId: (int) $job->getKey(),
                calculationType: $discountRevision->calculation_type->value,
                rate: (string) $discountRevision->rate,
                sourceAmount: (string) $job->job_discount_amount,
                allocationMethod: AllocationMethod::Manual,
                isSystemGenerated: true,
                description: (string) $discountRevision->reason,
            );
        }

        return new CreateInvoiceData(
            tenantId: (int) $job->tenant_id,
            invoiceType: InvoiceType::Service,
            direction: InvoiceDirection::Outbound,
            invoiceDate: $invoiceDate,
            organizationUnitId: $job->organization_unit_id,
            partyType: 'customer',
            partyId: (int) ($job->bill_to_customer_id ?? $job->customer_id),
            dueDate: $dueDate,
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            notes: $notes,
            createdBy: $createdBy,
            lines: $invoiceLines,
            adjustments: $adjustments,
            sources: [new InvoiceSourceData(
                tenantId: (int) $job->tenant_id,
                sourceType: 'vehicle_service_job',
                sourceId: (int) $job->getKey(),
                organizationUnitId: $job->organization_unit_id,
                sourceDocumentNumber: (string) $job->job_number,
                sourceDocumentDate: $job->job_date->toDateString(),
                sourceSubtotal: (string) $job->subtotal,
                sourceAdjustmentTotal: (string) $job->job_discount_amount,
                sourceGrandTotal: (string) $job->grand_total,
                invoicedAmount: $selectedTotal,
                allocatedAdjustmentAmount: $allocatedJobDiscount,
            )],
            sourceLines: $sourceLines,
            postingPlan: $this->postingPlans->outbound(
                FinancePostingProfileCode::VehicleServiceInvoice,
                $invoiceDate,
                FinanceAccountRoleCode::ServiceRevenue,
                $this->math->sub($baseAmount, $allocatedJobDiscount),
                $taxAmount,
                description: 'Vehicle service invoice '.$job->job_number,
            ),
        );
    }

    public function hasRemainingBillableLines(VehicleServiceJob $job): bool
    {
        foreach ($job->lines()
            ->where('is_billable', true)
            ->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
            ->get() as $line) {
            if ($this->math->compare(
                $this->invoicedQuantity((int) $job->tenant_id, (int) $line->getKey()),
                (string) $line->quantity,
            ) < 0) {
                return true;
            }
        }

        return false;
    }

    private function invoicedQuantity(int $tenantId, int $lineId): string
    {
        return $this->math->normalize((string) InvoiceSourceLine::query()
            ->where('tenant_id', $tenantId)
            ->where('source_line_type', 'vehicle_service_job_line')
            ->where('source_line_id', $lineId)
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
                InvoiceStatus::Reversed->value,
            ]))
            ->sum('invoiced_quantity'));
    }

    private function previouslyAllocatedDiscount(int $tenantId, int $discountRevisionId): string
    {
        return $this->math->normalize((string) InvoiceAdjustmentAllocation::query()
            ->where('tenant_id', $tenantId)
            ->where('source_adjustment_type', 'vehicle_service_job_discount')
            ->where('source_adjustment_id', $discountRevisionId)
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
                InvoiceStatus::Reversed->value,
            ]))
            ->sum('allocated_amount'));
    }

    private function invoiceLineType(VehicleServiceLineSourceType $source): InvoiceLineType
    {
        return match ($source) {
            VehicleServiceLineSourceType::ServiceItem => InvoiceLineType::Service,
            VehicleServiceLineSourceType::LabourItem => InvoiceLineType::Labour,
            default => InvoiceLineType::Item,
        };
    }

    private function assertInvoiceable(VehicleServiceJob $job): void
    {
        if (! in_array($job->status, [
            VehicleServiceJobStatus::Completed,
            VehicleServiceJobStatus::Invoiced,
        ], true)) {
            throw new InvalidArgumentException('Only completed service jobs can be invoiced.');
        }
    }
}
