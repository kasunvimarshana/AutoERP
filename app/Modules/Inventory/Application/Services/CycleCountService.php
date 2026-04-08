<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\CycleCountServiceInterface;
use Modules\Inventory\Domain\Entities\CycleCount;
use Modules\Inventory\Domain\Entities\CycleCountLine;
use Modules\Inventory\Domain\Events\CycleCountCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountLineRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;

class CycleCountService implements CycleCountServiceInterface
{
    public function __construct(
        private readonly CycleCountRepositoryInterface $cycleCountRepository,
        private readonly CycleCountLineRepositoryInterface $cycleCountLineRepository,
    ) {}

    public function getCycleCount(string $tenantId, string $id): CycleCount
    {
        $count = $this->cycleCountRepository->findById($tenantId, $id);

        if ($count === null) {
            throw new NotFoundException('CycleCount', $id);
        }

        return $count;
    }

    public function getAllCycleCounts(string $tenantId): array
    {
        return $this->cycleCountRepository->findAll($tenantId);
    }

    public function createCycleCount(string $tenantId, array $data): CycleCount
    {
        return DB::transaction(function () use ($tenantId, $data): CycleCount {
            $now = now();
            $count = new CycleCount(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                warehouseId: $data['warehouse_id'],
                locationId: $data['location_id'] ?? null,
                status: 'pending',
                scheduledAt: new \DateTimeImmutable($data['scheduled_at']),
                completedAt: null,
                notes: $data['notes'] ?? null,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->cycleCountRepository->save($count);

            Event::dispatch(new CycleCountCreated($count));

            return $count;
        });
    }

    public function startCycleCount(string $tenantId, string $id): CycleCount
    {
        return DB::transaction(function () use ($tenantId, $id): CycleCount {
            $existing = $this->getCycleCount($tenantId, $id);

            $updated = new CycleCount(
                id: $existing->id,
                tenantId: $existing->tenantId,
                warehouseId: $existing->warehouseId,
                locationId: $existing->locationId,
                status: 'in_progress',
                scheduledAt: $existing->scheduledAt,
                completedAt: null,
                notes: $existing->notes,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->cycleCountRepository->save($updated);

            return $updated;
        });
    }

    public function completeCycleCount(string $tenantId, string $id): CycleCount
    {
        return DB::transaction(function () use ($tenantId, $id): CycleCount {
            $existing = $this->getCycleCount($tenantId, $id);

            $updated = new CycleCount(
                id: $existing->id,
                tenantId: $existing->tenantId,
                warehouseId: $existing->warehouseId,
                locationId: $existing->locationId,
                status: 'completed',
                scheduledAt: $existing->scheduledAt,
                completedAt: now(),
                notes: $existing->notes,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->cycleCountRepository->save($updated);

            return $updated;
        });
    }

    public function cancelCycleCount(string $tenantId, string $id): CycleCount
    {
        return DB::transaction(function () use ($tenantId, $id): CycleCount {
            $existing = $this->getCycleCount($tenantId, $id);

            $updated = new CycleCount(
                id: $existing->id,
                tenantId: $existing->tenantId,
                warehouseId: $existing->warehouseId,
                locationId: $existing->locationId,
                status: 'cancelled',
                scheduledAt: $existing->scheduledAt,
                completedAt: $existing->completedAt,
                notes: $existing->notes,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->cycleCountRepository->save($updated);

            return $updated;
        });
    }

    public function updateCycleCount(string $tenantId, string $id, array $data): CycleCount
    {
        return DB::transaction(function () use ($tenantId, $id, $data): CycleCount {
            $existing = $this->getCycleCount($tenantId, $id);

            $updated = new CycleCount(
                id: $existing->id,
                tenantId: $existing->tenantId,
                warehouseId: $data['warehouse_id'] ?? $existing->warehouseId,
                locationId: array_key_exists('location_id', $data) ? $data['location_id'] : $existing->locationId,
                status: $data['status'] ?? $existing->status,
                scheduledAt: isset($data['scheduled_at'])
                    ? new \DateTimeImmutable($data['scheduled_at'])
                    : $existing->scheduledAt,
                completedAt: $existing->completedAt,
                notes: array_key_exists('notes', $data) ? $data['notes'] : $existing->notes,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->cycleCountRepository->save($updated);

            return $updated;
        });
    }

    public function deleteCycleCount(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getCycleCount($tenantId, $id);
            $this->cycleCountRepository->delete($tenantId, $id);
        });
    }

    public function addCycleCountLine(string $tenantId, string $cycleCountId, array $data): CycleCountLine
    {
        return DB::transaction(function () use ($tenantId, $cycleCountId, $data): CycleCountLine {
            $this->getCycleCount($tenantId, $cycleCountId);

            $now = now();
            $line = new CycleCountLine(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                cycleCountId: $cycleCountId,
                productId: $data['product_id'],
                variantId: $data['variant_id'] ?? null,
                systemQty: (float) $data['system_qty'],
                countedQty: null,
                variance: null,
                batchNumber: $data['batch_number'] ?? null,
                lotNumber: $data['lot_number'] ?? null,
                serialNumber: $data['serial_number'] ?? null,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->cycleCountLineRepository->save($line);

            return $line;
        });
    }

    public function updateCycleCountLine(string $tenantId, string $lineId, float $countedQty): CycleCountLine
    {
        return DB::transaction(function () use ($tenantId, $lineId, $countedQty): CycleCountLine {
            $existing = $this->cycleCountLineRepository->findById($tenantId, $lineId);

            if ($existing === null) {
                throw new NotFoundException('CycleCountLine', $lineId);
            }

            $variance = $countedQty - $existing->systemQty;

            $updated = new CycleCountLine(
                id: $existing->id,
                tenantId: $existing->tenantId,
                cycleCountId: $existing->cycleCountId,
                productId: $existing->productId,
                variantId: $existing->variantId,
                systemQty: $existing->systemQty,
                countedQty: $countedQty,
                variance: $variance,
                batchNumber: $existing->batchNumber,
                lotNumber: $existing->lotNumber,
                serialNumber: $existing->serialNumber,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->cycleCountLineRepository->save($updated);

            return $updated;
        });
    }
}
