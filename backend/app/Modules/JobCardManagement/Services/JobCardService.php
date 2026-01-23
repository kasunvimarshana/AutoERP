<?php

namespace App\Modules\JobCardManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\JobCardManagement\Events\JobCardOpened;
use App\Modules\JobCardManagement\Events\JobCardClosed;
use App\Modules\JobCardManagement\Events\JobCardAssigned;
use App\Modules\JobCardManagement\Repositories\JobCardRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JobCardService extends BaseService
{
    public function __construct(JobCardRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After job card creation hook
     */
    protected function afterCreate(Model $jobCard, array $data): void
    {
        event(new JobCardOpened($jobCard));
    }

    /**
     * Open a new job card
     */
    public function open(array $data): Model
    {
        $data['status'] = 'open';
        $data['opened_at'] = now();
        $data['job_card_number'] = $this->generateJobCardNumber();

        return $this->create($data);
    }

    /**
     * Close a job card
     */
    public function close(int $jobCardId, ?array $completionData = []): Model
    {
        try {
            DB::beginTransaction();

            $jobCard = $this->repository->findOrFail($jobCardId);
            
            $jobCard->status = 'closed';
            $jobCard->closed_at = now();
            $jobCard->actual_completion_date = $completionData['completion_date'] ?? now();
            
            if (isset($completionData['notes'])) {
                $jobCard->completion_notes = $completionData['notes'];
            }

            $jobCard->save();

            event(new JobCardClosed($jobCard));

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign job card to a technician
     */
    public function assign(int $jobCardId, int $technicianId): Model
    {
        try {
            DB::beginTransaction();

            $jobCard = $this->repository->findOrFail($jobCardId);
            $jobCard->assigned_technician_id = $technicianId;
            $jobCard->status = 'in_progress';
            $jobCard->assigned_at = now();
            $jobCard->save();

            event(new JobCardAssigned($jobCard));

            DB::commit();

            return $jobCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update job card status
     */
    public function updateStatus(int $jobCardId, string $status): Model
    {
        return $this->update($jobCardId, ['status' => $status]);
    }

    /**
     * Get active job cards
     */
    public function getActive()
    {
        return $this->repository->getActive();
    }

    /**
     * Get job cards by technician
     */
    public function getByTechnician(int $technicianId)
    {
        return $this->repository->getByTechnician($technicianId);
    }

    /**
     * Get job cards by status
     */
    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Calculate total labor cost
     */
    public function calculateLaborCost(int $jobCardId): float
    {
        $jobCard = $this->repository->findOrFail($jobCardId);
        return $jobCard->tasks->sum('labor_cost') ?? 0;
    }

    /**
     * Calculate total parts cost
     */
    public function calculatePartsCost(int $jobCardId): float
    {
        $jobCard = $this->repository->findOrFail($jobCardId);
        return $jobCard->tasks->sum('parts_cost') ?? 0;
    }

    /**
     * Generate unique job card number
     */
    protected function generateJobCardNumber(): string
    {
        $prefix = 'JC';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return "{$prefix}-{$date}-{$random}";
    }
}
