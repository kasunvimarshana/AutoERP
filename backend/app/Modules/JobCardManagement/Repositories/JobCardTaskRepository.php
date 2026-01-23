<?php

namespace App\Modules\JobCardManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\JobCardManagement\Models\JobCardTask;

class JobCardTaskRepository extends BaseRepository
{
    public function __construct(JobCardTask $model)
    {
        parent::__construct($model);
    }

    /**
     * Search job card tasks by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('task_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['task_type'])) {
            $query->where('task_type', $criteria['task_type']);
        }

        if (!empty($criteria['job_card_id'])) {
            $query->where('job_card_id', $criteria['job_card_id']);
        }

        if (!empty($criteria['assigned_to'])) {
            $query->where('assigned_to', $criteria['assigned_to']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['jobCard', 'assignedUser'])
            ->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get tasks by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['jobCard', 'assignedUser'])->get();
    }

    /**
     * Get tasks assigned to user
     */
    public function getAssignedTo(int $userId)
    {
        return $this->model->where('assigned_to', $userId)->with(['jobCard'])->get();
    }

    /**
     * Get tasks for job card
     */
    public function getForJobCard(int $jobCardId)
    {
        return $this->model->where('job_card_id', $jobCardId)->with(['assignedUser'])->get();
    }

    /**
     * Get pending tasks
     */
    public function getPending()
    {
        return $this->model->where('status', 'pending')->with(['jobCard', 'assignedUser'])->get();
    }

    /**
     * Get in progress tasks
     */
    public function getInProgress()
    {
        return $this->model->where('status', 'in_progress')->with(['jobCard', 'assignedUser'])->get();
    }

    /**
     * Get completed tasks
     */
    public function getCompleted()
    {
        return $this->model->where('status', 'completed')->with(['jobCard', 'assignedUser'])->get();
    }

    /**
     * Get overdue tasks
     */
    public function getOverdue()
    {
        return $this->model->where('status', '!=', 'completed')
            ->whereNotNull('estimated_completion_time')
            ->where('estimated_completion_time', '<', now())
            ->with(['jobCard', 'assignedUser'])
            ->get();
    }
}
