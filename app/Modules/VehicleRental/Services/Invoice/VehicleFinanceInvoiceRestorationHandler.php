<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Invoice;

use Illuminate\Database\Eloquent\Builder;
use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceRestorationContext;
use Modules\VehicleRental\Constants\VehicleRentalInvoiceSource;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;

final class VehicleFinanceInvoiceRestorationHandler implements InvoiceSourceRestorationHandlerInterface
{
    public function supports(InvoiceSourceRestorationContext $context): bool
    {
        foreach ($context->sourceLines as $sourceLine) {
            if ($sourceLine->sourceType === VehicleRentalInvoiceSource::VEHICLE_FINANCE_INSTALLMENT) {
                return true;
            }
        }

        return false;
    }

    public function restore(InvoiceSourceRestorationContext $context): void
    {
        $installmentIds = [];
        foreach ($context->sourceLines as $sourceLine) {
            if ($sourceLine->sourceType === VehicleRentalInvoiceSource::VEHICLE_FINANCE_INSTALLMENT) {
                $installmentIds[] = $sourceLine->sourceId;
            }
        }

        foreach (array_values(array_unique($installmentIds)) as $installmentId) {
            $installment = $this->scope(VehicleFinanceInstallment::query(), $context)
                ->lockForUpdate()
                ->findOrFail($installmentId);

            if ((int) $installment->invoice_id !== $context->invoiceId) {
                continue;
            }

            $installment->forceFill([
                'invoice_id' => null,
                'row_version' => (int) $installment->row_version + 1,
            ])->save();
        }
    }

    private function scope(Builder $query, InvoiceSourceRestorationContext $context): Builder
    {
        $query->where('tenant_id', $context->tenantId);

        return $context->organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $context->organizationUnitId);
    }
}
