<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Models\VehicleServiceInvoiceLink;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceInvoiceIntegrationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceCreationService $invoices,
        private readonly VehicleServiceStatusService $statuses,
    ) {}

    /** @param array<int, string> $lineQuantities */
    public function preview(VehicleServiceJob $job, string $invoiceDate, array $lineQuantities = []): InvoiceCalculationResult
    {
        $this->assertInvoiceable($job);

        return $this->invoices->preview($this->invoiceData($job, $invoiceDate, $lineQuantities));
    }

    /** @param array<int, string> $lineQuantities */
    public function create(
        VehicleServiceJob $job,
        string $invoiceDate,
        array $lineQuantities = [],
        ?string $dueDate = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $notes = null,
        ?int $createdBy = null,
    ): Invoice {
        $this->assertInvoiceable($job);

        return DB::transaction(function () use ($job, $invoiceDate, $lineQuantities, $dueDate, $currencyId, $exchangeRate, $notes, $createdBy): Invoice {
            $data = $this->invoiceData($job, $invoiceDate, $lineQuantities, $dueDate, $currencyId, $exchangeRate, $notes, $createdBy);
            $invoice = $this->invoices->create($data);
            VehicleServiceInvoiceLink::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'invoice_id' => $invoice->getKey(),
                'source_line_total' => $this->math->sum(array_map(
                    static fn (InvoiceLineData $line): string => (string) $line->lineTotal,
                    $data->lines,
                )),
                'allocated_adjustment_total' => '0.000000',
                'invoice_total' => (string) $invoice->grand_total,
                'status' => 'active',
            ]);

            if ($this->hasRemainingBillableLines($job)) {
                return $invoice;
            }
            if ($job->status === VehicleServiceJobStatus::Completed) {
                $this->statuses->change($job, VehicleServiceJobStatus::Invoiced, $createdBy);
            }

            return $invoice;
        });
    }

    /** @param array<int, string> $lineQuantities */
    private function invoiceData(
        VehicleServiceJob $job,
        string $invoiceDate,
        array $lineQuantities,
        ?string $dueDate = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $notes = null,
        ?int $createdBy = null,
    ): CreateInvoiceData {
        $job->load('lines.item');
        $invoiceLines = [];
        $sourceLines = [];
        $selectedTotal = '0.000000';
        $lineNumber = 1;

        foreach ($job->lines->where('is_billable', true) as $line) {
            $previouslyInvoiced = $this->invoicedQuantity((int) $job->tenant_id, (int) $line->getKey());
            $remaining = $this->math->sub((string) $line->quantity, $previouslyInvoiced);
            $quantity = $this->math->normalize($lineQuantities[(int) $line->getKey()] ?? $remaining);
            if ($this->math->isZero($quantity)) {
                continue;
            }
            if ($this->math->compare($quantity, $remaining) > 0) {
                throw new InvalidArgumentException('Invoice quantity cannot exceed service job line remaining quantity.');
            }
            $ratio = $this->math->div($quantity, (string) $line->quantity, 12);
            $discount = $this->math->mul((string) $line->discount_amount, $ratio);
            $tax = $this->math->mul((string) $line->tax_amount, $ratio);
            $charge = $this->math->mul((string) $line->charge_amount, $ratio);
            $lineTotal = $this->math->add(
                $this->math->sub($this->math->mul($quantity, (string) $line->unit_price), $discount),
                $this->math->add($tax, $charge),
            );
            $selectedTotal = $this->math->add($selectedTotal, $lineTotal);

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
            partyId: (int) $job->customer_id,
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
        );
    }

    private function hasRemainingBillableLines(VehicleServiceJob $job): bool
    {
        foreach ($job->lines()->where('is_billable', true)->get() as $line) {
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
        if (! in_array($job->status, [VehicleServiceJobStatus::Completed, VehicleServiceJobStatus::Invoiced], true)) {
            throw new InvalidArgumentException('Only completed service jobs can be invoiced.');
        }
    }
}
