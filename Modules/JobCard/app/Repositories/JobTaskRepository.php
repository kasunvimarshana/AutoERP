<?php

declare(strict_types=1);

namespace Modules\JobCard\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\JobCard\Models\JobTask;

/**
 * JobTask Repository
 *
 * Handles data access for JobTask model
 */
class JobTaskRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new JobTask;
    }

    /**
     * Get tasks for job card
     */
    public function getForJobCard(int $jobCardId): Collection
    {
        return $this->model->newQuery()->where('job_card_id', $jobCardId)->get();
    }

    /**
     * Get tasks by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()->where('status', $status)->get();
    }

    /**
     * Get tasks assigned to user
     */
    public function getAssignedTo(int $userId): Collection
    {
        return $this->model->newQuery()->where('assigned_to', $userId)->get();
    }

    /**
     * Get pending tasks for job card
     */
    public function getPendingForJobCard(int $jobCardId): Collection
    {
        return $this->model->newQuery()
            ->where('job_card_id', $jobCardId)
            ->where('status', 'pending')
            ->get();
    }

    /**
     * Get completed tasks for job card
     */
    public function getCompletedForJobCard(int $jobCardId): Collection
    {
        return $this->model->newQuery()
            ->where('job_card_id', $jobCardId)
            ->where('status', 'completed')
            ->get();
    }
}
