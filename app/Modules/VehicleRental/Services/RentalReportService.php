<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\InvoiceSource;
use Modules\VehicleRental\Constants\VehicleRentalFinancialDocument;
use Modules\VehicleRental\Constants\VehicleRentalSource;
use Modules\VehicleRental\Enums\RentalCalculationSide;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;
use Modules\VehicleRental\Models\RentalCalculation;
use Modules\VehicleRental\Models\RentalRunningChart;

final class RentalReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @return array<string, mixed> */
    public function summary(
        int $tenantId,
        ?int $organizationUnitId,
        ?string $dateFrom,
        ?string $dateTo,
    ): array {
        $chartQuery = RentalRunningChart::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('status', RentalRunningChartStatus::Finalized->value)
            ->where('active_marker', true);
        $this->applyChartPeriod($chartQuery, $dateFrom, $dateTo);

        $calculationQuery = RentalCalculation::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('status', RentalCalculationStatus::Calculated->value)
            ->where('active_marker', true);
        $this->applyCalculationPeriod($calculationQuery, $dateFrom, $dateTo);
        $calculations = $calculationQuery->get();
        $customerCalculations = $calculations->filter(
            static fn (RentalCalculation $calculation): bool => $calculation->side === RentalCalculationSide::Customer,
        );
        $ownerCalculations = $calculations->filter(
            static fn (RentalCalculation $calculation): bool => $calculation->side === RentalCalculationSide::Owner,
        );
        $customerDocuments = $this->financialTotals($tenantId, $organizationUnitId, $customerCalculations);
        $ownerDocuments = $this->financialTotals($tenantId, $organizationUnitId, $ownerCalculations);
        $customerSubtotal = $this->sumCalculationSubtotal($customerCalculations);
        $ownerSubtotal = $this->sumCalculationSubtotal($ownerCalculations);

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'running_charts' => [
                'count' => (clone $chartQuery)->count(),
                'commercial_km' => $this->math->normalize((string) (clone $chartQuery)->sum('commercial_km')),
            ],
            'customer' => [
                'calculation_count' => $customerCalculations->count(),
                'subtotal_amount' => $customerSubtotal,
                ...$customerDocuments,
            ],
            'owner' => [
                'calculation_count' => $ownerCalculations->count(),
                'subtotal_amount' => $ownerSubtotal,
                ...$ownerDocuments,
            ],
            'gross_margin_before_tax' => $this->math->sub($customerSubtotal, $ownerSubtotal),
        ];
    }

    private function applyChartPeriod(Builder $query, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateFrom !== null) {
            $query->whereDate('operational_date', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->whereDate('operational_date', '<=', $dateTo);
        }
    }

    private function applyCalculationPeriod(Builder $query, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateFrom !== null) {
            $query->whereDate('period_end', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->whereDate('period_start', '<=', $dateTo);
        }
    }

    private function sumCalculationSubtotal(Collection $calculations): string
    {
        return $this->math->sum(
            $calculations
                ->map(static fn (RentalCalculation $calculation): string => (string) $calculation->subtotal_amount)
                ->values()
                ->all(),
        );
    }

    /** @return array{financial_document_count: int, document_total: string, outstanding_amount: string} */
    private function financialTotals(
        int $tenantId,
        ?int $organizationUnitId,
        Collection $calculations,
    ): array {
        if ($calculations->isEmpty()) {
            return [
                'financial_document_count' => 0,
                'document_total' => VehicleRentalFinancialDocument::ZERO,
                'outstanding_amount' => VehicleRentalFinancialDocument::ZERO,
            ];
        }

        $sources = InvoiceSource::query()
            ->where('tenant_id', $tenantId)
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $organizationUnitId),
            )
            ->where('source_type', VehicleRentalSource::CALCULATION_DOCUMENT)
            ->whereIn('source_id', $calculations->pluck('id'))
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
                InvoiceStatus::Reversed->value,
            ]))
            ->with('invoice')
            ->get()
            ->unique('invoice_id');

        return [
            'financial_document_count' => $sources->count(),
            'document_total' => $this->math->sum(
                $sources->map(static fn (InvoiceSource $source): string => (string) $source->invoice->grand_total)->all(),
            ),
            'outstanding_amount' => $this->math->sum(
                $sources->map(static fn (InvoiceSource $source): string => (string) $source->invoice->balance_due)->all(),
            ),
        ];
    }
}
