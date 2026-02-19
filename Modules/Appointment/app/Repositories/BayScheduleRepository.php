<?php

declare(strict_types=1);

namespace Modules\Appointment\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Appointment\Models\BaySchedule;

/**
 * BaySchedule Repository
 *
 * Handles data access for BaySchedule model
 */
class BayScheduleRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new BaySchedule;
    }

    /**
     * Get schedules for a bay
     */
    public function getForBay(int $bayId): Collection
    {
        return $this->model->newQuery()
            ->where('bay_id', $bayId)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get schedules for an appointment
     */
    public function getForAppointment(int $appointmentId): Collection
    {
        return $this->model->newQuery()
            ->where('appointment_id', $appointmentId)
            ->get();
    }

    /**
     * Get active schedules for a bay
     */
    public function getActiveForBay(int $bayId): Collection
    {
        return $this->model->newQuery()
            ->where('bay_id', $bayId)
            ->whereIn('status', ['scheduled', 'active'])
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get schedules in time range for a bay
     */
    public function getForBayInTimeRange(int $bayId, string $startTime, string $endTime): Collection
    {
        return $this->model->newQuery()
            ->where('bay_id', $bayId)
            ->whereIn('status', ['scheduled', 'active'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime) {
                        $q2->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->get();
    }

    /**
     * Check if bay is available for time range
     */
    public function isBayAvailable(int $bayId, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('bay_id', $bayId)
            ->whereIn('status', ['scheduled', 'active'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime) {
                        $q2->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Get schedules with relations
     */
    public function getAllWithRelations(): Collection
    {
        return $this->model->newQuery()
            ->with(['bay', 'appointment'])
            ->get();
    }

    /**
     * Delete schedules for appointment
     */
    public function deleteForAppointment(int $appointmentId): int
    {
        return $this->model->newQuery()
            ->where('appointment_id', $appointmentId)
            ->delete();
    }
}
