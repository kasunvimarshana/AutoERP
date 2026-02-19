<?php

declare(strict_types=1);

namespace Modules\JobCard\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\JobCard\Models\JobCard;

/**
 * JobCard Repository
 *
 * Handles data access for JobCard model
 */
class JobCardRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new JobCard;
    }

    /**
     * Find job card by job number
     */
    public function findByJobNumber(string $jobNumber): ?JobCard
    {
        /** @var JobCard|null */
        return $this->findOneBy(['job_number' => $jobNumber]);
    }

    /**
     * Check if job number exists
     */
    public function jobNumberExists(string $jobNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('job_number', $jobNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get job cards by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()->where('status', $status)->get();
    }

    /**
     * Get job cards by priority
     */
    public function getByPriority(string $priority): Collection
    {
        return $this->model->newQuery()->where('priority', $priority)->get();
    }

    /**
     * Get job cards for branch
     */
    public function getForBranch(int $branchId): Collection
    {
        return $this->model->newQuery()->where('branch_id', $branchId)->get();
    }

    /**
     * Get job cards for technician
     */
    public function getForTechnician(int $technicianId): Collection
    {
        return $this->model->newQuery()->where('technician_id', $technicianId)->get();
    }

    /**
     * Get job cards for customer
     */
    public function getForCustomer(int $customerId): Collection
    {
        return $this->model->newQuery()->where('customer_id', $customerId)->get();
    }

    /**
     * Get job cards for vehicle
     */
    public function getForVehicle(int $vehicleId): Collection
    {
        return $this->model->newQuery()->where('vehicle_id', $vehicleId)->get();
    }

    /**
     * Get active job cards
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();
    }

    /**
     * Get job card with all relationships
     */
    public function findWithRelations(int $id): ?JobCard
    {
        /** @var JobCard|null */
        return $this->model->newQuery()
            ->with([
                'customer',
                'vehicle',
                'branch',
                'technician',
                'supervisor',
                'tasks',
                'inspectionItems',
                'parts',
            ])
            ->find($id);
    }

    /**
     * Get job cards with tasks
     */
    public function getAllWithTasks(): Collection
    {
        return $this->model->newQuery()->with('tasks')->get();
    }

    /**
     * Get job cards with parts
     */
    public function getAllWithParts(): Collection
    {
        return $this->model->newQuery()->with('parts')->get();
    }

    /**
     * Search job cards
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('job_number', 'like', "%{$query}%")
                    ->orWhere('notes', 'like', "%{$query}%")
                    ->orWhere('customer_complaints', 'like', "%{$query}%");
            })
            ->get();
    }

    /**
     * Get overdue job cards
     */
    public function getOverdue(): Collection
    {
        return $this->model->newQuery()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($query) {
                $query->whereNotNull('estimated_hours')
                    ->whereRaw('TIMESTAMPDIFF(HOUR, started_at, NOW()) > estimated_hours');
            })
            ->get();
    }
}
