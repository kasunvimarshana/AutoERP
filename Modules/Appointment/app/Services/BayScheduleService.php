<?php

declare(strict_types=1);

namespace Modules\Appointment\Services;

use App\Core\Services\BaseService;
use Modules\Appointment\Repositories\BayScheduleRepository;

/**
 * BaySchedule Service
 *
 * Contains business logic for BaySchedule operations
 */
class BayScheduleService extends BaseService
{
    /**
     * BayScheduleService constructor
     */
    public function __construct(BayScheduleRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get schedules for a bay
     */
    public function getForBay(int $bayId): mixed
    {
        return $this->repository->getForBay($bayId);
    }

    /**
     * Get schedules for an appointment
     */
    public function getForAppointment(int $appointmentId): mixed
    {
        return $this->repository->getForAppointment($appointmentId);
    }

    /**
     * Get active schedules for a bay
     */
    public function getActiveForBay(int $bayId): mixed
    {
        return $this->repository->getActiveForBay($bayId);
    }

    /**
     * Get schedules in time range for a bay
     */
    public function getForBayInTimeRange(int $bayId, string $startTime, string $endTime): mixed
    {
        return $this->repository->getForBayInTimeRange($bayId, $startTime, $endTime);
    }

    /**
     * Check if bay is available
     */
    public function isBayAvailable(int $bayId, string $startTime, string $endTime): bool
    {
        return $this->repository->isBayAvailable($bayId, $startTime, $endTime);
    }
}
