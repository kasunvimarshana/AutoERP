<?php

declare(strict_types=1);

namespace Modules\Appointment\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Appointment\Models\Bay;

/**
 * Bay Repository
 *
 * Handles data access for Bay model
 */
class BayRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new Bay;
    }

    /**
     * Get available bays for a branch
     */
    public function getAvailableForBranch(int $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('status', 'available')
            ->get();
    }

    /**
     * Get bays by type for a branch
     */
    public function getByTypeForBranch(int $branchId, string $type): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('bay_type', $type)
            ->get();
    }

    /**
     * Check if bay number exists for branch
     */
    public function bayNumberExistsForBranch(int $branchId, string $bayNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('bay_number', $bayNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get bays with their schedules
     */
    public function getAllWithSchedules(): Collection
    {
        return $this->model->newQuery()->with('schedules')->get();
    }

    /**
     * Get bay with schedules by ID
     */
    public function findWithSchedules(int $id): ?Bay
    {
        /** @var Bay|null */
        return $this->model->newQuery()->with('schedules')->find($id);
    }

    /**
     * Get available bays for time range
     */
    public function getAvailableForTimeRange(int $branchId, string $startTime, string $endTime): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('status', 'available')
            ->whereDoesntHave('schedules', function ($query) use ($startTime, $endTime) {
                $query->whereIn('status', ['scheduled', 'active'])
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->whereBetween('start_time', [$startTime, $endTime])
                            ->orWhereBetween('end_time', [$startTime, $endTime])
                            ->orWhere(function ($q2) use ($startTime, $endTime) {
                                $q2->where('start_time', '<=', $startTime)
                                    ->where('end_time', '>=', $endTime);
                            });
                    });
            })
            ->get();
    }
}
