<?php

namespace App\Modules\JobCardManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\JobCardManagement\Events\TaskCompleted;
use App\Modules\JobCardManagement\Repositories\JobCardTaskRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JobCardTaskService extends BaseService
{
    public function __construct(JobCardTaskRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Complete a task
     */
    public function complete(int $taskId, array $completionData = []): Model
    {
        try {
            DB::beginTransaction();

            $task = $this->repository->findOrFail($taskId);
            $task->status = 'completed';
            $task->completed_at = now();
            
            if (isset($completionData['actual_duration'])) {
                $task->actual_duration = $completionData['actual_duration'];
            }
            
            if (isset($completionData['notes'])) {
                $task->completion_notes = $completionData['notes'];
            }

            $task->save();

            event(new TaskCompleted($task));

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Start a task
     */
    public function start(int $taskId): Model
    {
        return $this->update($taskId, [
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }

    /**
     * Pause a task
     */
    public function pause(int $taskId): Model
    {
        return $this->update($taskId, ['status' => 'paused']);
    }

    /**
     * Get tasks by job card
     */
    public function getByJobCard(int $jobCardId)
    {
        return $this->repository->getByJobCard($jobCardId);
    }

    /**
     * Get tasks by status
     */
    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Assign task to technician
     */
    public function assignToTechnician(int $taskId, int $technicianId): Model
    {
        return $this->update($taskId, ['assigned_technician_id' => $technicianId]);
    }

    /**
     * Update task progress
     */
    public function updateProgress(int $taskId, int $progressPercentage): Model
    {
        return $this->update($taskId, ['progress_percentage' => $progressPercentage]);
    }
}
