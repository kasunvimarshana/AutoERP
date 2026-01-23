<?php

declare(strict_types=1);

namespace Modules\JobCard\Services;

use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\JobCard\Repositories\JobTaskRepository;

/**
 * JobTask Service
 *
 * Contains business logic for JobTask operations
 */
class JobTaskService extends BaseService
{
    /**
     * JobTaskService constructor
     */
    public function __construct(JobTaskRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new task
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): mixed
    {
        DB::beginTransaction();
        try {
            if (! isset($data['status'])) {
                $data['status'] = 'pending';
            }

            $task = parent::create($data);

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add task to job card
     *
     * @param  array<string, mixed>  $taskData
     */
    public function addToJobCard(int $jobCardId, array $taskData): mixed
    {
        DB::beginTransaction();
        try {
            $taskData['job_card_id'] = $jobCardId;
            $task = $this->create($taskData);

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update task status
     */
    public function updateStatus(int $id, string $status): mixed
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Complete task
     */
    public function complete(int $id, ?float $actualTime = null): mixed
    {
        DB::beginTransaction();
        try {
            $data = ['status' => 'completed'];

            if ($actualTime !== null) {
                $data['actual_time'] = $actualTime;
            }

            $task = $this->update($id, $data);

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get tasks for job card
     */
    public function getForJobCard(int $jobCardId): mixed
    {
        return $this->repository->getForJobCard($jobCardId);
    }

    /**
     * Get pending tasks for job card
     */
    public function getPendingForJobCard(int $jobCardId): mixed
    {
        return $this->repository->getPendingForJobCard($jobCardId);
    }

    /**
     * Get tasks assigned to user
     */
    public function getAssignedTo(int $userId): mixed
    {
        return $this->repository->getAssignedTo($userId);
    }
}
