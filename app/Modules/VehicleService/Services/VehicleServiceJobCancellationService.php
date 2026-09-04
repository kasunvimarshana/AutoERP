<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\InventoryFacade;
use Modules\VehicleService\Constants\VehicleServiceFinanceSource;
use Modules\VehicleService\Constants\VehicleServicePermission;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceJobCancellationService
{
    private const ZERO_AMOUNT = '0.000000';

    public function __construct(
        private readonly PermissionCheckerInterface $permissions,
        private readonly InventoryFacade $inventory,
        private readonly VehicleServiceInventoryFinanceService $finance,
        private readonly DecimalMath $math,
        private readonly VehicleServiceBillingProtection $billing,
    ) {}

    /** @return array<string, mixed> */
    public function preview(VehicleServiceJob $job, ?int $actorId): array
    {
        $this->assertAuthorized($job, $actorId);
        $blockers = $this->blockers($job);
        $lines = $job->lines()->whereNotNull('inventory_movement_id')
            ->with(['inventoryMovement.baseUom', 'inventoryMovement.warehouse', 'inventoryMovement.warehouseLocation'])->get();
        $stock = [];
        $value = self::ZERO_AMOUNT;
        foreach ($lines as $line) {
            $movement = $line->inventoryMovement;
            if ($movement === null || $movement->status !== InventoryStatus::Posted) {
                $blockers[] = 'The stock issue for '.$line->description.' is missing or already reversed. Reconcile it before cancellation.';

                continue;
            }
            $value = $this->math->add($value, (string) $movement->total_cost);
            $stock[] = [
                'description' => $line->description,
                'quantity' => (string) $movement->quantity,
                'uom' => $movement->baseUom?->name,
                'warehouse' => $movement->warehouse?->name,
                'location' => $movement->warehouseLocation?->name,
            ];
        }

        return [
            'row_version' => (int) $job->row_version,
            'can_cancel' => $blockers === [],
            'blockers' => $blockers,
            'stock_returns' => $stock,
            'inventory_value' => $value,
            'commission_amount' => (string) $job->commission_cost_total,
        ];
    }

    /** Called only by the status service after locking the vehicle/job timeline. */
    public function reverse(VehicleServiceJob $job, ?int $actorId, ?string $reason): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Job cancellation requires the status transaction and its job lock.');
        }
        $this->assertAuthorized($job, $actorId);
        $reason = trim((string) $reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A cancellation reason is required.');
        }
        $blockers = $this->blockers($job, true);
        if ($blockers !== []) {
            throw new InvalidArgumentException(implode(' ', $blockers));
        }

        $lines = $job->lines()->whereNotNull('inventory_movement_id')->reorder('id')->lockForUpdate()->get();
        foreach ($lines as $line) {
            $movement = InventoryMovement::query()->lockForUpdate()->find($line->inventory_movement_id);
            if ($movement === null
                || $movement->status !== InventoryStatus::Posted
                || $movement->direction !== InventoryDirection::Out
                || (int) $movement->tenant_id !== (int) $job->tenant_id
                || $movement->organization_unit_id !== $job->organization_unit_id
                || $movement->source_type !== VehicleServiceFinanceSource::JOB
                || (int) $movement->source_id !== (int) $job->getKey()
                || $movement->source_line_type !== VehicleServiceFinanceSource::JOB_LINE
                || (int) $movement->source_line_id !== (int) $line->getKey()) {
                throw new InvalidArgumentException('The stock issue for '.$line->description.' is missing, already reversed, or does not belong to this job. Reconcile it before cancellation.');
            }

            $this->inventory->reverse($movement, $actorId);
            // Missing non-zero postings are an error, not a zero-cost issue.
            try {
                $this->finance->reverseIssue($job, $movement, $actorId, $reason);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException('Cannot cancel job: finance reversal for '.$line->description.' failed. '.$exception->getMessage(), previous: $exception);
            }
        }
        // Preserve line/assignment amounts and original issue links. Cancelled job
        // status removes commissions from payable reports without erasing history.
    }

    private function assertAuthorized(VehicleServiceJob $job, ?int $actorId): void
    {
        if ($actorId === null || ! $this->permissions->allows($actorId, (int) $job->tenant_id, VehicleServicePermission::JOBS_TRANSITION)) {
            throw new AuthorizationException('You do not have permission to cancel vehicle service jobs.');
        }
        if ($job->status === VehicleServiceJobStatus::Completed
            && ! $this->permissions->allows($actorId, (int) $job->tenant_id, VehicleServicePermission::JOBS_CANCEL_COMPLETED)) {
            throw new AuthorizationException('Cancelling a completed job requires the completed-job cancellation permission.');
        }
    }

    /** @return list<string> */
    private function blockers(VehicleServiceJob $job, bool $lockDocuments = false): array
    {
        $blockers = [];
        if (! in_array($job->status, [
            VehicleServiceJobStatus::Draft, VehicleServiceJobStatus::Inspected,
            VehicleServiceJobStatus::InProgress, VehicleServiceJobStatus::Completed,
        ], true)) {
            $blockers[] = 'This job status does not allow cancellation.';
        }

        return [...$blockers, ...$this->billing->blockers($job, $lockDocuments)];
    }
}
