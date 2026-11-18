<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Invoice\Models\Invoice;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceQuickCompletionService
{
    public function __construct(
        private readonly VehicleServiceStatusService $statuses,
        private readonly VehicleServiceInvoiceIntegrationService $invoices,
    ) {}

    public function completeAndBill(VehicleServiceJob $job, int $expectedVersion, ?int $actorId): Invoice
    {
        return DB::transaction(function () use ($job, $expectedVersion, $actorId): Invoice {
            $completed = $this->statuses->change(
                $job,
                VehicleServiceJobStatus::Completed,
                $actorId,
                expectedVersion: $expectedVersion,
            );

            if (! $completed->lines()
                ->where('is_billable', true)
                ->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
                ->exists()) {
                throw new InvalidArgumentException('Add at least one billable job line before completing and billing this job.');
            }

            return $this->invoices->create(
                $completed,
                $completed->job_date->toDateString(),
                createdBy: $actorId,
                expectedVersion: (int) $completed->row_version,
            );
        });
    }
}
