<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
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
        $lineNumber = 1;
        $selectionProvided = $lineQuantities !== [];
        $validLineIds = $lines->pluck('id')->map(fn ($id): int => (int) $id)->all();

        if (array_diff(array_keys($lineQuantities), $validLineIds) !== []) {
            throw new InvalidArgumentException(
                'Invoice selection contains a line that is not billable for this service job.',
            );
        }

        foreach ($lines as $line) {
            if ($selectionProvided && ! array_key_exists((int) $line->getKey(), $lineQuantities)) {
                continue;
            }

            $previouslyInvoiced = $this->invoicedQuantity(
                (int) $job->tenant_id,
                (int) $line->getKey(),
            );
            $remaining = $this->math->sub((string) $line->quantity, $previouslyInvoiced);
            if ($this->math->compare($remaining, self::ZERO) <= 0) {
                continue;
            }

            $quantity = $this->math->normalize(
                $lineQuantities[(int) $line->getKey()] ?? $remaining,
            );
            if ($this->math->isZero($quantity)) {
                continue;
            }
            if ($this->math->compare($quantity, $remaining) > 0) {
                throw new InvalidArgumentException(
                    'Invoice quantity cannot exceed service job line remaining quantity.',
                );
            }

            $ratio = $this->math->div($quantity, (string) $line->quantity, 12);
            $discount = $this->math->mul((string) $line->discount_amount, $ratio);
            $tax = $this->math->mul((string) $line->tax_amount, $ratio);
            $charge = $this->math->mul((string) $line->charge_amount, $ratio);
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
            sources: [new InvoiceSourceData(
                tenantId: (int) $job->tenant_id,
                sourceType: 'vehicle_service_job',
                sourceId: (int) $job->getKey(),
                organizationUnitId: $job->organization_unit_id,
                sourceDocumentNumber: (string) $job->job_number,
                sourceDocumentDate: $job->job_date->toDateString(),
                sourceSubtotal: (string) $job->subtotal,
                sourceGrandTotal: (string) $job->grand_total,
                invoicedAmount: $selectedTotal,
            )],
            sourceLines: $sourceLines,
            postingPlan: $this->postingPlans->outbound(
                FinancePostingProfileCode::VehicleServiceInvoice,
                $invoiceDate,
                FinanceAccountRoleCode::ServiceRevenue,
                $baseAmount,
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
            ]))
            ->sum('invoiced_quantity'));
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
