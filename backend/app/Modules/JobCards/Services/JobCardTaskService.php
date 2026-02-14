<?php

namespace App\Modules\JobCards\Services;

use App\Core\BaseService;
use App\Modules\JobCards\Repositories\JobCardTaskRepository;

class JobCardTaskService extends BaseService
{
    protected JobCardTaskRepository $repository;

    public function __construct(JobCardTaskRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createTask(array $data)
    {
        return $this->transaction(function () use ($data) {
            $task = $this->repository->create($data);
            $this->logActivity('Job Card Task created', ['task_id' => $task->id]);
            return $task;
        });
    }

    public function updateTask(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            if ($result) {
                $this->logActivity('Job Card Task updated', ['task_id' => $id]);
            }
            return $result;
        });
    }

    public function getTask(int $id)
    {
        return $this->repository->with(['jobCard', 'sku', 'completedBy', 'tenant'])->find($id);
    }

    public function getAllTasks()
    {
        return $this->repository->with(['jobCard', 'sku', 'completedBy'])->all();
    }

    public function deleteTask(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            if ($result) {
                $this->logActivity('Job Card Task deleted', ['task_id' => $id]);
            }
            return $result;
        });
    }

    public function getByJobCard(int $jobCardId)
    {
        return $this->repository->getByJobCard($jobCardId);
    }

    public function getByTaskType(string $taskType)
    {
        return $this->repository->getByTaskType($taskType);
    }

    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function startTask(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $task = $this->repository->find($id);

            if (!$task) {
                return false;
            }

            if ($task->status === 'completed') {
                throw new \Exception('Cannot start a completed task');
            }

            $result = $this->repository->update($id, ['status' => 'in_progress']);
            if ($result) {
                $this->logActivity('Job Card Task started', ['task_id' => $id]);
            }
            return $result;
        });
    }
}
