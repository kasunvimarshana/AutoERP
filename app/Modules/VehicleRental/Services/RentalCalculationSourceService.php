<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalCalculationSourceKind;
use Modules\VehicleRental\Enums\RentalCalculationSourceStatus;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalUsageFactStatus;
use Modules\VehicleRental\Models\RentalCalculationRun;
use Modules\VehicleRental\Models\RentalCalculationSource;
use Modules\VehicleRental\Models\RentalExpenseAllocation;
use Modules\VehicleRental\Models\RentalUsageContext;
use Modules\VehicleRental\Models\RentalUsageFact;

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

        $expenseAllocations = RentalExpenseAllocation::query()
            ->where('tenant_id', $run->tenant_id)
            ->whereIn('id', $expenseAllocationIds)
            ->get()
            ->keyBy(fn (RentalExpenseAllocation $allocation): int => (int) $allocation->getKey());

        foreach ($expenseAllocationIds as $expenseAllocationId) {
            $allocation = $expenseAllocations->get((int) $expenseAllocationId);
            if ($allocation === null) {
                throw new InvalidArgumentException('Calculation source expense allocation no longer exists.');
            }

            $run->sources()->create([
                'tenant_id' => $run->tenant_id,
                'organization_unit_id' => $run->organization_unit_id,
                'source_kind' => RentalCalculationSourceKind::ExpenseAllocation->value,
                'usage_context_id' => null,
                'expense_allocation_id' => $expenseAllocationId,
                'status' => RentalCalculationSourceStatus::Draft->value,
                'metadata' => [
                    'expense_allocation_version' => (int) $allocation->row_version,
                    'expense_allocation_status' => (string) $allocation->status,
                ],
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
            ->whereHas('run', fn (Builder $query): Builder => $query
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
        $sources = $run->sources()->orderBy('id')->lockForUpdate()->get();
        if ($sources->isEmpty()) {
            throw new InvalidArgumentException('Calculation run has no governed source allocations.');
        }

        $usageContextIds = $sources->pluck('usage_context_id')->filter()->unique()->values();
        if ($usageContextIds->isNotEmpty()) {
            $contexts = RentalUsageContext::query()
                ->where('tenant_id', $run->tenant_id)
                ->whereIn('id', $usageContextIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'context_fingerprint'])
                ->keyBy(fn (RentalUsageContext $context): int => (int) $context->getKey());
            $facts = RentalUsageFact::query()
                ->where('tenant_id', $run->tenant_id)
                ->whereIn('usage_context_id', $usageContextIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (RentalUsageFact $fact): int => (int) $fact->usage_context_id);

            foreach ($sources->whereNotNull('usage_context_id') as $source) {
                $context = $contexts->get((int) $source->usage_context_id);
                $fact = $facts->get((int) $source->usage_context_id);
                $metadata = $source->metadata ?? [];
                if ($context === null
                    || $fact === null
                    || $fact->status !== RentalUsageFactStatus::Approved
                    || (int) ($metadata['usage_fact_id'] ?? 0) !== (int) $fact->getKey()
                    || (int) ($metadata['usage_fact_version'] ?? 0) !== (int) $fact->row_version
                    || (string) ($metadata['context_fingerprint'] ?? '') !== (string) $context->context_fingerprint) {
                    throw new InvalidArgumentException(
                        'One or more commercial usage facts changed after this calculation was prepared.',
                    );
                }
            }

            $usageConflict = RentalCalculationSource::query()
                ->where('calculation_run_id', '!=', $run->getKey())
                ->whereIn('usage_context_id', $usageContextIds)
                ->where('status', RentalCalculationSourceStatus::Approved->value)
                ->whereHas('run', fn (Builder $query): Builder => $query
                    ->where('calculation_status', RentalCalculationStatus::Approved->value))
                ->exists();
            if ($usageConflict) {
                throw new InvalidArgumentException(
                    'One or more commercial usage facts are already consumed by another approved calculation.',
                );
            }
        }

        $expenseAllocationIds = $sources->pluck('expense_allocation_id')->filter()->unique()->values();
        if ($expenseAllocationIds->isNotEmpty()) {
            $expenseAllocations = RentalExpenseAllocation::query()
                ->where('tenant_id', $run->tenant_id)
                ->whereIn('id', $expenseAllocationIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (RentalExpenseAllocation $allocation): int => (int) $allocation->getKey());

            foreach ($sources->whereNotNull('expense_allocation_id') as $source) {
                $allocation = $expenseAllocations->get((int) $source->expense_allocation_id);
                $metadata = $source->metadata ?? [];
                if ($allocation === null
                    || (string) $allocation->status !== 'approved'
                    || (int) ($metadata['expense_allocation_version'] ?? 0) !== (int) $allocation->row_version
                    || (string) ($metadata['expense_allocation_status'] ?? '') !== (string) $allocation->status) {
                    throw new InvalidArgumentException(
                        'One or more expense allocations changed after this calculation was prepared.',
                    );
                }
            }

            $expenseConflict = RentalCalculationSource::query()
                ->where('calculation_run_id', '!=', $run->getKey())
                ->whereIn('expense_allocation_id', $expenseAllocationIds)
                ->where('status', RentalCalculationSourceStatus::Approved->value)
                ->whereHas('run', fn (Builder $query): Builder => $query
                    ->where('calculation_status', RentalCalculationStatus::Approved->value))
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
            'row_version' => DB::raw('row_version + 1'),
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }
}
