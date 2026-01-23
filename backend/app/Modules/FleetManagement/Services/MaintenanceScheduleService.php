<?php

namespace App\Modules\FleetManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\FleetManagement\Events\MaintenanceScheduled;
use App\Modules\FleetManagement\Events\MaintenanceDue;
use App\Modules\FleetManagement\Repositories\MaintenanceScheduleRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MaintenanceScheduleService extends BaseService
{
    public function __construct(MaintenanceScheduleRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After schedule creation hook
     */
    protected function afterCreate(Model $schedule, array $data): void
    {
        event(new MaintenanceScheduled($schedule));
    }

    /**
     * Calculate next service date
     */
    public function calculateNextService(int $scheduleId): \DateTime
    {
        $schedule = $this->repository->findOrFail($scheduleId);
        
        $lastServiceDate = $schedule->last_service_date ?? $schedule->created_at;
        $intervalDays = $schedule->interval_days ?? 90;
        
        return (clone $lastServiceDate)->modify("+{$intervalDays} days");
    }

    /**
     * Calculate next service based on mileage
     */
    public function calculateNextServiceMileage(int $scheduleId, int $currentMileage): int
    {
        $schedule = $this->repository->findOrFail($scheduleId);
        
        $lastServiceMileage = $schedule->last_service_mileage ?? 0;
        $intervalMileage = $schedule->interval_mileage ?? 5000;
        
        return $lastServiceMileage + $intervalMileage;
    }

    /**
     * Update next service date
     */
    public function updateNextServiceDate(int $scheduleId): Model
    {
        $nextServiceDate = $this->calculateNextService($scheduleId);
        
        return $this->update($scheduleId, [
            'next_service_date' => $nextServiceDate
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(int $scheduleId, array $completionData): Model
    {
        try {
            DB::beginTransaction();

            $schedule = $this->repository->findOrFail($scheduleId);
            $schedule->status = 'completed';
            $schedule->last_service_date = $completionData['service_date'] ?? now();
            $schedule->last_service_mileage = $completionData['mileage'] ?? null;
            $schedule->completed_at = now();
            $schedule->save();

            // Calculate and set next service
            $nextServiceDate = $this->calculateNextService($scheduleId);
            $schedule->next_service_date = $nextServiceDate;
            $schedule->status = 'scheduled';
            $schedule->save();

            DB::commit();

            return $schedule;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get schedules by vehicle
     */
    public function getByVehicle(int $vehicleId)
    {
        return $this->repository->getByVehicle($vehicleId);
    }

    /**
     * Get due schedules
     */
    public function getDue()
    {
        return $this->repository->getDue();
    }

    /**
     * Get upcoming schedules
     */
    public function getUpcoming(int $days = 30)
    {
        return $this->repository->getUpcoming($days);
    }

    /**
     * Check if maintenance is due
     */
    public function isDue(int $scheduleId): bool
    {
        $schedule = $this->repository->findOrFail($scheduleId);
        
        if ($schedule->next_service_date) {
            return $schedule->next_service_date <= now();
        }
        
        return false;
    }

    /**
     * Send due notifications
     */
    public function sendDueNotifications(): int
    {
        $dueSchedules = $this->getDue();
        $count = 0;

        foreach ($dueSchedules as $schedule) {
            try {
                event(new MaintenanceDue($schedule));
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $count;
    }

    /**
     * Skip maintenance
     */
    public function skip(int $scheduleId, string $reason): Model
    {
        return $this->update($scheduleId, [
            'status' => 'skipped',
            'skip_reason' => $reason,
            'skipped_at' => now()
        ]);
    }
}
