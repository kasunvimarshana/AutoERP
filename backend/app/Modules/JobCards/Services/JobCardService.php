<?php

namespace App\Modules\JobCards\Services;

use App\Core\BaseService;
use App\Modules\JobCards\Repositories\JobCardRepository;
use App\Modules\JobCards\Repositories\JobCardTaskRepository;

class JobCardService extends BaseService
{
    protected JobCardRepository $repository;
    protected JobCardTaskRepository $taskRepository;

    public function __construct(
        JobCardRepository $repository,
        JobCardTaskRepository $taskRepository
    ) {
        $this->repository = $repository;
        $this->taskRepository = $taskRepository;
    }

    public function createJobCard(array $data)
    {
        return $this->transaction(function () use ($data) {
            $tasks = $data['tasks'] ?? [];
            unset($data['tasks']);

            $jobCard = $this->repository->create($data);

            if (!empty($tasks)) {
                foreach ($tasks as $task) {
                    $task['tenant_id'] = $data['tenant_id'];
                    $task['job_card_id'] = $jobCard->id;
                    $this->taskRepository->create($task);
                }
            }

            $this->logActivity('Job Card created', ['job_card_id' => $jobCard->id]);
            return $jobCard->load('tasks');
        });
    }

    public function updateJobCard(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            unset($data['tasks']);

            $result = $this->repository->update($id, $data);
            if ($result) {
                $this->logActivity('Job Card updated', ['job_card_id' => $id]);
            }
            return $result;
        });
    }

    public function getJobCard(int $id)
    {
        return $this->repository->with(['tasks.sku', 'customer', 'vehicle', 'technician', 'appointment', 'tenant'])->find($id);
    }

    public function getAllJobCards()
    {
        return $this->repository->with(['tasks', 'customer', 'vehicle', 'technician'])->all();
    }

    public function deleteJobCard(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            if ($result) {
                $this->logActivity('Job Card deleted', ['job_card_id' => $id]);
            }
            return $result;
        });
    }

    public function startWork(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $jobCard = $this->repository->find($id);

            if (!$jobCard) {
                return false;
            }

            if ($jobCard->status !== 'open' && $jobCard->status !== 'in_progress') {
                throw new \Exception('Only open or in_progress job cards can start work');
            }

            $result = $this->repository->update($id, ['status' => 'in_progress']);
            if ($result) {
                $this->logActivity('Job Card work started', ['job_card_id' => $id]);
            }
            return $result;
        });
    }

    public function completeTask(int $taskId, int $userId): bool
    {
        return $this->transaction(function () use ($taskId, $userId) {
            $task = $this->taskRepository->find($taskId);

            if (!$task) {
                return false;
            }

            if ($task->status === 'completed') {
                throw new \Exception('Task is already completed');
            }

            $result = $this->taskRepository->update($taskId, [
                'status' => 'completed',
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            if ($result) {
                $this->logActivity('Job Card Task completed', [
                    'task_id' => $taskId,
                    'job_card_id' => $task->job_card_id,
                    'completed_by' => $userId,
                ]);
            }

            return $result;
        });
    }

    public function closeJobCard(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $jobCard = $this->repository->find($id);

            if (!$jobCard) {
                return false;
            }

            if ($jobCard->status === 'completed' || $jobCard->status === 'cancelled') {
                throw new \Exception('Job card is already closed');
            }

            $tasks = $this->taskRepository->getByJobCard($id);
            $allCompleted = $tasks->every(function ($task) {
                return $task->status === 'completed';
            });

            if (!$allCompleted) {
                throw new \Exception('Cannot close job card with incomplete tasks');
            }

            $actualCost = $this->taskRepository->getTotalByJobCard($id);

            $result = $this->repository->update($id, [
                'status' => 'completed',
                'closed_date' => now(),
                'actual_cost' => $actualCost,
            ]);

            if ($result) {
                $this->logActivity('Job Card closed', [
                    'job_card_id' => $id,
                    'actual_cost' => $actualCost,
                ]);
            }

            return $result;
        });
    }

    public function cancelJobCard(int $id, ?string $reason = null): bool
    {
        return $this->transaction(function () use ($id, $reason) {
            $jobCard = $this->repository->find($id);

            if (!$jobCard) {
                return false;
            }

            if ($jobCard->status === 'completed') {
                throw new \Exception('Cannot cancel a completed job card');
            }

            $updateData = [
                'status' => 'cancelled',
                'closed_date' => now(),
            ];

            if ($reason) {
                $notes = $jobCard->notes ? $jobCard->notes . "\n" : '';
                $updateData['notes'] = $notes . "Cancellation reason: " . $reason;
            }

            $result = $this->repository->update($id, $updateData);

            if ($result) {
                $this->logActivity('Job Card cancelled', [
                    'job_card_id' => $id,
                    'reason' => $reason,
                ]);
            }

            return $result;
        });
    }

    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function getByCustomer(int $customerId)
    {
        return $this->repository->getByCustomer($customerId);
    }

    public function getByVehicle(int $vehicleId)
    {
        return $this->repository->getByVehicle($vehicleId);
    }

    public function getByTechnician(int $technicianId)
    {
        return $this->repository->getByTechnician($technicianId);
    }

    public function getByDateRange(string $startDate, string $endDate)
    {
        return $this->repository->getByDateRange($startDate, $endDate);
    }

    public function getTotalCost(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->repository->getTotalCostByTenant($tenantId, $startDate, $endDate);
    }
}
