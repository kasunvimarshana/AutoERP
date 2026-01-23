<?php

declare(strict_types=1);

namespace Modules\JobCard\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\JobCard\Models\JobCard;
use Modules\JobCard\Repositories\JobCardRepository;
use Modules\JobCard\Repositories\JobPartRepository;
use Modules\JobCard\Repositories\JobTaskRepository;

/**
 * JobCard Service
 *
 * Contains business logic for JobCard operations
 */
class JobCardService extends BaseService
{
    /**
     * JobCardService constructor
     */
    public function __construct(
        JobCardRepository $repository,
        private readonly JobTaskRepository $taskRepository,
        private readonly JobPartRepository $partRepository
    ) {
        parent::__construct($repository);
    }

    /**
     * Create a new job card
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        DB::beginTransaction();
        try {
            if (! isset($data['job_number'])) {
                $data['job_number'] = $this->generateUniqueJobNumber();
            }

            if (! isset($data['status'])) {
                $data['status'] = 'pending';
            }

            if (! isset($data['priority'])) {
                $data['priority'] = 'normal';
            }

            $jobCard = parent::create($data);

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update job card
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): mixed
    {
        DB::beginTransaction();
        try {
            $jobCard = parent::update($id, $data);

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get job card with all relations
     */
    public function getWithRelations(int $id): mixed
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Start job card
     */
    public function start(int $id): mixed
    {
        DB::beginTransaction();
        try {
            $jobCard = $this->repository->findOrFail($id);

            if ($jobCard->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Job card can only be started from pending status.'],
                ]);
            }

            $data = [
                'status' => 'in_progress',
                'started_at' => now(),
            ];

            $jobCard = $this->update($id, $data);

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Pause job card
     */
    public function pause(int $id): mixed
    {
        DB::beginTransaction();
        try {
            $jobCard = $this->repository->findOrFail($id);

            if ($jobCard->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'status' => ['Only in-progress job cards can be paused.'],
                ]);
            }

            $data = ['status' => 'on_hold'];
            $jobCard = $this->update($id, $data);

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Resume job card
     */
    public function resume(int $id): mixed
    {
        DB::beginTransaction();
        try {
            $jobCard = $this->repository->findOrFail($id);

            if ($jobCard->status !== 'on_hold') {
                throw ValidationException::withMessages([
                    'status' => ['Only on-hold job cards can be resumed.'],
                ]);
            }

            $data = ['status' => 'in_progress'];
            $jobCard = $this->update($id, $data);

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Complete job card
     */
    public function complete(int $id): mixed
    {
        DB::beginTransaction();
        try {
            $jobCard = $this->repository->findOrFail($id);

            if (! in_array($jobCard->status, ['in_progress', 'quality_check'])) {
                throw ValidationException::withMessages([
                    'status' => ['Job card must be in progress or quality check to complete.'],
                ]);
            }

            $this->calculateTotals($id);

            $data = [
                'status' => 'completed',
                'completed_at' => now(),
            ];

            $jobCard = $this->update($id, $data);

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update status
     */
    public function updateStatus(int $id, string $status): mixed
    {
        DB::beginTransaction();
        try {
            $validStatuses = ['pending', 'in_progress', 'on_hold', 'waiting_parts', 'quality_check', 'completed', 'cancelled'];

            if (! in_array($status, $validStatuses)) {
                throw ValidationException::withMessages([
                    'status' => ['Invalid status value.'],
                ]);
            }

            $data = ['status' => $status];

            if ($status === 'in_progress' && ! $this->repository->findOrFail($id)->started_at) {
                $data['started_at'] = now();
            }

            if ($status === 'completed') {
                $data['completed_at'] = now();
                $this->calculateTotals($id);
            }

            $jobCard = $this->update($id, $data);

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign technician
     */
    public function assignTechnician(int $id, int $technicianId): mixed
    {
        return $this->update($id, ['technician_id' => $technicianId]);
    }

    /**
     * Calculate totals for job card
     */
    public function calculateTotals(int $id): mixed
    {
        DB::beginTransaction();
        try {
            $partsTotal = $this->partRepository->getTotalForJobCard($id);

            $tasks = $this->taskRepository->getForJobCard($id);
            $actualHours = $tasks->sum('actual_time') ?: 0;

            $laborRate = 50.00;
            $laborTotal = $actualHours * $laborRate;

            $grandTotal = $partsTotal + $laborTotal;

            $data = [
                'parts_total' => $partsTotal,
                'labor_total' => $laborTotal,
                'actual_hours' => $actualHours,
                'grand_total' => $grandTotal,
            ];

            $jobCard = $this->update($id, $data);

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get job cards for technician
     */
    public function getForTechnician(int $technicianId): mixed
    {
        return $this->repository->getForTechnician($technicianId);
    }

    /**
     * Get job cards for customer
     */
    public function getForCustomer(int $customerId): mixed
    {
        return $this->repository->getForCustomer($customerId);
    }

    /**
     * Get job cards for vehicle
     */
    public function getForVehicle(int $vehicleId): mixed
    {
        return $this->repository->getForVehicle($vehicleId);
    }

    /**
     * Get active job cards
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Search job cards
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }

    /**
     * Get job card statistics
     */
    public function getStatistics(int $id): array
    {
        $jobCard = $this->repository->findWithRelations($id);

        if (! $jobCard) {
            throw new ServiceException('Job card not found');
        }

        return [
            'total_tasks' => $jobCard->tasks->count(),
            'completed_tasks' => $jobCard->tasks->where('status', 'completed')->count(),
            'pending_tasks' => $jobCard->tasks->where('status', 'pending')->count(),
            'total_parts' => $jobCard->parts->count(),
            'total_inspections' => $jobCard->inspectionItems->count(),
            'parts_total' => $jobCard->parts_total,
            'labor_total' => $jobCard->labor_total,
            'grand_total' => $jobCard->grand_total,
            'progress_percentage' => $this->calculateProgress($jobCard),
        ];
    }

    /**
     * Calculate job card progress percentage
     */
    protected function calculateProgress(JobCard $jobCard): float
    {
        $totalTasks = $jobCard->tasks->count();

        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $jobCard->tasks->where('status', 'completed')->count();

        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    /**
     * Generate unique job number
     */
    protected function generateUniqueJobNumber(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $jobNumber = JobCard::generateJobNumber();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new ServiceException('Failed to generate unique job number after maximum attempts');
            }
        } while ($this->repository->jobNumberExists($jobNumber));

        return $jobNumber;
    }
}
