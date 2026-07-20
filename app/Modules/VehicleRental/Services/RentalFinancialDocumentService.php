<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceSource;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\VehicleRental\Constants\VehicleRentalSource;
use Modules\VehicleRental\DTOs\RentalFinancialDocumentData;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Models\RentalCalculation;

final class RentalFinancialDocumentService
{
    /** @var list<string> */
    private const INVOICE_RELATIONS = [
        'customer',
        'supplier',
        'currency',
        'lines',
        'sources',
        'sourceLines',
        'adjustments',
        'balance',
        'postingPlan',
    ];

    public function __construct(
        private readonly RentalFinancialDocumentDataFactory $factory,
        private readonly InvoiceCreationService $invoices,
        private readonly InvoiceStatusService $invoiceStatuses,
    ) {}

    public function create(RentalCalculation $calculation, RentalFinancialDocumentData $data): Invoice
    {
        return DB::transaction(function () use ($calculation, $data): Invoice {
            $calculation = RentalCalculation::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->with(['agreement.customer', 'agreement.supplier', 'currency', 'lines'])
                ->lockForUpdate()
                ->findOrFail($calculation->getKey());

            if ((int) $calculation->row_version !== $data->expectedVersion) {
                throw new InvalidArgumentException(
                    'Rental calculation was changed by another request. Reload it before creating the financial document.',
                );
            }
            if ($calculation->status !== RentalCalculationStatus::Calculated
                || $calculation->active_marker !== true) {
                throw new InvalidArgumentException('Only an active calculated Rental snapshot can create a financial document.');
            }

            $existing = $this->financialDocument($calculation, lockRows: true);
            if ($existing instanceof Invoice) {
                return $existing;
            }

            $invoice = $this->invoices->create($this->factory->make($calculation, $data));
            $invoice = $this->invoiceStatuses->transition($invoice, InvoiceStatus::Approved, $data->actorId);
            $invoice = $this->invoiceStatuses->transition($invoice, InvoiceStatus::Posted, $data->actorId);

            return $invoice->refresh()->load(self::INVOICE_RELATIONS);
        });
    }

    public function assertNoActiveFinancialDocument(RentalCalculation $calculation): void
    {
        if ($this->financialDocument($calculation, lockRows: true) instanceof Invoice) {
            throw new InvalidArgumentException(
                'Reverse or cancel the Rental financial document before cancelling its calculation.',
            );
        }
    }

    public function constrainToActiveFinancialDocuments(Builder $query, bool $outstandingOnly): void
    {
        $terminalStatuses = self::terminalStatuses();
        $query->whereExists(function ($documents) use ($outstandingOnly, $terminalStatuses): void {
            $documents
                ->selectRaw('1')
                ->from('invoice_sources as rental_invoice_sources')
                ->join('invoices as rental_invoices', function ($join): void {
                    $join->on('rental_invoices.id', '=', 'rental_invoice_sources.invoice_id')
                        ->on('rental_invoices.tenant_id', '=', 'rental_invoice_sources.tenant_id');
                })
                ->whereColumn('rental_invoice_sources.tenant_id', 'vehicle_rental_calculations.tenant_id')
                ->whereColumn('rental_invoice_sources.source_id', 'vehicle_rental_calculations.id')
                ->where('rental_invoice_sources.source_type', VehicleRentalSource::CALCULATION_DOCUMENT)
                ->whereNotIn('rental_invoices.status', $terminalStatuses)
                ->whereNull('rental_invoices.deleted_at')
                ->where(function ($scope): void {
                    $scope
                        ->whereColumn(
                            'rental_invoice_sources.organization_unit_id',
                            'vehicle_rental_calculations.organization_unit_id',
                        )
                        ->orWhere(function ($nullScope): void {
                            $nullScope
                                ->whereNull('rental_invoice_sources.organization_unit_id')
                                ->whereNull('vehicle_rental_calculations.organization_unit_id');
                        });
                });

            if ($outstandingOnly) {
                $documents->where('rental_invoices.balance_due', '>', 0);
            }
        });
    }

    public function attachFinancialDocuments(Collection $calculations): void
    {
        if ($calculations->isEmpty()) {
            return;
        }

        $first = $calculations->first();
        if (! $first instanceof RentalCalculation) {
            return;
        }
        $calculationIds = $calculations->map(
            static fn (RentalCalculation $calculation): int => (int) $calculation->getKey(),
        );

        $sources = InvoiceSource::query()
            ->where('tenant_id', (int) $first->tenant_id)
            ->when(
                $first->organization_unit_id === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $first->organization_unit_id),
            )
            ->where('source_type', VehicleRentalSource::CALCULATION_DOCUMENT)
            ->whereIn('source_id', $calculationIds)
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', self::terminalStatuses()))
            ->with(['invoice.customer', 'invoice.supplier', 'invoice.currency'])
            ->orderByDesc('id')
            ->get()
            ->unique('source_id')
            ->keyBy('source_id');

        foreach ($calculations as $calculation) {
            $source = $sources->get((int) $calculation->getKey());
            $calculation->setRelation('financialDocument', $source?->invoice);
        }
    }

    public function financialDocument(RentalCalculation $calculation, bool $lockRows = false): ?Invoice
    {
        $query = InvoiceSource::query()
            ->where('tenant_id', (int) $calculation->tenant_id)
            ->when(
                $calculation->organization_unit_id === null,
                fn ($scope) => $scope->whereNull('organization_unit_id'),
                fn ($scope) => $scope->where('organization_unit_id', $calculation->organization_unit_id),
            )
            ->where('source_type', VehicleRentalSource::CALCULATION_DOCUMENT)
            ->where('source_id', $calculation->getKey())
            ->whereHas('invoice', fn ($scope) => $scope->whereNotIn('status', self::terminalStatuses()))
            ->with(['invoice.customer', 'invoice.supplier', 'invoice.currency'])
            ->orderByDesc('id');
        if ($lockRows) {
            $query->lockForUpdate();
        }

        return $query->first()?->invoice;
    }

    /** @return list<string> */
    private static function terminalStatuses(): array
    {
        return [
            InvoiceStatus::Cancelled->value,
            InvoiceStatus::Void->value,
            InvoiceStatus::Reversed->value,
        ];
    }
}
