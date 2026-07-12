<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Invoice;

use Illuminate\Database\Eloquent\Builder;
use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceRestorationContext;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\InvoiceAdjustment;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\VehicleRental\Enums\RentalDocumentStatus;
use Modules\VehicleRental\Models\RentalCalculationRun;

final class RentalInvoiceRestorationHandler implements InvoiceSourceRestorationHandlerInterface
{
    private const SOURCE_TYPE = 'rental_calculation_run';

    public function supports(InvoiceSourceRestorationContext $context): bool
    {
        foreach ($context->sourceLines as $sourceLine) {
            if ($sourceLine->sourceType === self::SOURCE_TYPE) {
                return true;
            }
        }

        return false;
    }

    public function restore(InvoiceSourceRestorationContext $context): void
    {
        $runIds = [];
        foreach ($context->sourceLines as $sourceLine) {
            if ($sourceLine->sourceType === self::SOURCE_TYPE) {
                $runIds[] = $sourceLine->sourceId;
            }
        }

        foreach (array_values(array_unique($runIds)) as $runId) {
            $run = $this->scope(RentalCalculationRun::query(), $context)
                ->lockForUpdate()
                ->findOrFail($runId);
            $run->forceFill([
                'document_status' => $this->hasOtherActiveDocuments($context, $runId)
                    ? RentalDocumentStatus::PartiallyGenerated->value
                    : RentalDocumentStatus::NotGenerated->value,
                'row_version' => (int) $run->row_version + 1,
            ])->save();
        }
    }

    private function hasOtherActiveDocuments(InvoiceSourceRestorationContext $context, int $runId): bool
    {
        $terminalStatuses = [
            InvoiceStatus::Cancelled->value,
            InvoiceStatus::Void->value,
            InvoiceStatus::Reversed->value,
        ];
        $hasSourceLines = InvoiceSourceLine::query()
            ->where('tenant_id', $context->tenantId)
            ->where('source_type', self::SOURCE_TYPE)
            ->where('source_id', $runId)
            ->where('invoice_id', '!=', $context->invoiceId)
            ->whereHas('invoice', fn (Builder $query): Builder => $query->whereNotIn('status', $terminalStatuses))
            ->exists();
        if ($hasSourceLines) {
            return true;
        }

        $lineIds = \Modules\VehicleRental\Models\RentalCalculationLine::query()
            ->where('tenant_id', $context->tenantId)
            ->where('rental_calculation_run_id', $runId)
            ->pluck('id');
        if ($lineIds->isEmpty()) {
            return false;
        }

        return InvoiceAdjustment::query()
            ->where('tenant_id', $context->tenantId)
            ->where('source_type', 'rental_calculation_line')
            ->whereIn('source_id', $lineIds)
            ->where('invoice_id', '!=', $context->invoiceId)
            ->whereHas('invoice', fn (Builder $query): Builder => $query->whereNotIn('status', $terminalStatuses))
            ->exists();
    }

    private function scope(Builder $query, InvoiceSourceRestorationContext $context): Builder
    {
        $query->where('tenant_id', $context->tenantId);

        return $context->organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $context->organizationUnitId);
    }
}
