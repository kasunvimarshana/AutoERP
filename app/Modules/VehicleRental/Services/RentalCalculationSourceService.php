<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalCalculationSourceKind;
use Modules\VehicleRental\Enums\RentalCalculationSourceStatus;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Models\RentalCalculationRun;
use Modules\VehicleRental\Models\RentalCalculationSource;
use Modules\VehicleRental\Models\RentalUsageContext;

final class RentalCalculationSourceService
{
    /**
     * @param Collection<int, RentalUsageContext> $contexts
     * @param Collection<int, int> $expenseAllocationIds
     */
    public function record(
        RentalCalculationRun $run,
        Collection $contexts,
        Collection $expenseAllocationIds,
        ?int $userId,
    ): void {
        foreach ($contexts as $context) {
            $run->sources()->create([
                'tenant_id' => $run->tenant_id,
                'organization_unit_id' => $run->organization_unit_id,
                'source_kind' => RentalCalculationSourceKind::UsageContext->value,
                'usage_context_id' => $context->getKey(),
                'expense_allocation_id' => null,
                'status' => RentalCalculationSourceStatus::Draft->value,
                'metadata' => [
                    'usage_fact_id' => $context->usageFact->getKey(),
                    'usage_fact_version' => (int) $context->usageFact->row_version,
                    'context_fingerprint' => $context->context_fingerprint,
                ],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        foreach ($expenseAllocationIds as $expenseAllocationId) {
            $run->sources()->create([
                'tenant_id' => $run->tenant_id,
                'organization_unit_id' => $run->organization_unit_id,
                'source_kind' => RentalCalculationSourceKind::ExpenseAllocation->value,
                'usage_context_id' => null,
                'expense_allocation_id' => $expenseAllocationId,
                'status' => RentalCalculationSourceStatus::Draft->value,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    /**
     * @param Collection<int, int> $usageContextIds
     */
    public function assertUsageContextsAvailable(Collection $usageContextIds): void
    {
        if ($usageContextIds->isEmpty()) {
            return;
        }

        $consumed = RentalCalculationSource::query()
            ->whereIn('usage_context_id', $usageContextIds)
            ->where('status', RentalCalculationSourceStatus::Approved->value)
            ->whereHas('run', fn ($query) => $query
                ->where('calculation_status', RentalCalculationStatus::Approved->value))
            ->exists();

        if ($consumed) {
            throw new InvalidArgumentException(
                'One or more commercial usage facts are already consumed by an approved calculation.',
            );
        }
    }

    public function assertAvailableForApproval(RentalCalculationRun $run): void
    {
        $sources = $run->sources()->lockForUpdate()->get();
        if ($sources->isEmpty()) {
            throw new InvalidArgumentException('Calculation run has no governed source allocations.');
        }

        $usageContextIds = $sources->pluck('usage_context_id')->filter()->unique();
        if ($usageContextIds->isNotEmpty()) {
            $usageConflict = RentalCalculationSource::query()
                ->where('calculation_run_id', '!=', $run->getKey())
                ->whereIn('usage_context_id', $usageContextIds)
                ->where('status', RentalCalculationSourceStatus::Approved->value)
                ->whereHas('run', fn ($query) => $query
                    ->where('calculation_status', RentalCalculationStatus::Approved->value))
                ->lockForUpdate()
                ->exists();
            if ($usageConflict) {
                throw new InvalidArgumentException(
                    'One or more commercial usage facts are already consumed by another approved calculation.',
                );
            }
        }

        $expenseAllocationIds = $sources->pluck('expense_allocation_id')->filter()->unique();
        if ($expenseAllocationIds->isNotEmpty()) {
            $expenseConflict = RentalCalculationSource::query()
                ->where('calculation_run_id', '!=', $run->getKey())
                ->whereIn('expense_allocation_id', $expenseAllocationIds)
                ->where('status', RentalCalculationSourceStatus::Approved->value)
                ->whereHas('run', fn ($query) => $query
                    ->where('calculation_status', RentalCalculationStatus::Approved->value))
                ->lockForUpdate()
                ->exists();
            if ($expenseConflict) {
                throw new InvalidArgumentException(
                    'One or more expense allocations are already consumed by another approved calculation.',
                );
            }
        }
    }

    public function transition(
        RentalCalculationRun $run,
        RentalCalculationStatus $status,
        ?int $userId,
    ): void {
        $sourceStatus = match ($status) {
            RentalCalculationStatus::Approved => RentalCalculationSourceStatus::Approved,
            RentalCalculationStatus::Reversed => RentalCalculationSourceStatus::Reversed,
            default => RentalCalculationSourceStatus::Draft,
        };

        $run->sources()->update([
            'status' => $sourceStatus->value,
            'row_version' => \DB::raw('row_version + 1'),
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }
}
