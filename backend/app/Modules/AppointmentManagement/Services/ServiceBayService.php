<?php

namespace App\Modules\AppointmentManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\AppointmentManagement\Repositories\ServiceBayRepository;
use Illuminate\Database\Eloquent\Model;

class ServiceBayService extends BaseService
{
    public function __construct(ServiceBayRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get available service bays for a time slot
     */
    public function getAvailableBays(\DateTime $startTime, \DateTime $endTime)
    {
        return $this->repository->getAvailableBays($startTime, $endTime);
    }

    /**
     * Mark service bay as occupied
     */
    public function markAsOccupied(int $bayId): Model
    {
        return $this->update($bayId, ['status' => 'occupied']);
    }

    /**
     * Mark service bay as available
     */
    public function markAsAvailable(int $bayId): Model
    {
        return $this->update($bayId, ['status' => 'available']);
    }

    /**
     * Mark service bay as under maintenance
     */
    public function markAsUnderMaintenance(int $bayId): Model
    {
        return $this->update($bayId, ['status' => 'maintenance']);
    }

    /**
     * Get service bays by status
     */
    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Check if bay is available
     */
    public function isAvailable(int $bayId, \DateTime $startTime, \DateTime $endTime): bool
    {
        return $this->repository->checkAvailability($bayId, $startTime, $endTime);
    }
}
