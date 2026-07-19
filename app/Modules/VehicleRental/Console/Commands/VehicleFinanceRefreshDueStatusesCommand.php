<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Console\Commands;

use Illuminate\Console\Command;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;
use Modules\VehicleRental\Services\VehicleFinanceInstallmentStatusRefreshService;

final class VehicleFinanceRefreshDueStatusesCommand extends Command
{
    protected $signature = 'vehicle-rental:finance-installments:refresh-due
        {--tenant-id= : Limit refresh to one tenant}
        {--organization-unit-id= : Limit refresh to one organization unit}
        {--date= : Business date used for due/overdue classification}';

    protected $description = 'Refresh vehicle-finance installment due, overdue, partial, and paid statuses.';

    public function handle(VehicleFinanceInstallmentStatusRefreshService $service): int
    {
        $tenantId = $this->option('tenant-id');
        $organizationUnitId = $this->option('organization-unit-id');
        $date = $this->option('date');

        $query = VehicleFinanceInstallment::query()
            ->select(['tenant_id', 'organization_unit_id'])
            ->distinct()
            ->orderBy('tenant_id')
            ->orderBy('organization_unit_id');

        if (is_numeric($tenantId)) {
            $query->where('tenant_id', (int) $tenantId);
        }
        if (is_numeric($organizationUnitId)) {
            $query->where('organization_unit_id', (int) $organizationUnitId);
        }

        $contexts = $query->get();
        $updated = 0;
        foreach ($contexts as $context) {
            $updated += $service->refresh(
                (int) $context->tenant_id,
                $context->organization_unit_id === null ? null : (int) $context->organization_unit_id,
                is_string($date) && $date !== '' ? $date : null,
            );
        }

        $this->info(sprintf('Checked %d vehicle-finance context(s); refreshed %d installment status transition(s).', $contexts->count(), $updated));

        return self::SUCCESS;
    }
}
