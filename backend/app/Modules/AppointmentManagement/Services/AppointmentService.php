<?php

namespace App\Modules\AppointmentManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\AppointmentManagement\Events\AppointmentCreated;
use App\Modules\AppointmentManagement\Events\AppointmentConfirmed;
use App\Modules\AppointmentManagement\Events\AppointmentCancelled;
use App\Modules\AppointmentManagement\Repositories\AppointmentRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AppointmentService extends BaseService
{
    public function __construct(AppointmentRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After appointment creation hook
     */
    protected function afterCreate(Model $appointment, array $data): void
    {
        event(new AppointmentCreated($appointment));
    }

    /**
     * Confirm an appointment
     */
    public function confirm(int $appointmentId): Model
    {
        try {
            DB::beginTransaction();

            $appointment = $this->repository->findOrFail($appointmentId);
            $appointment->status = 'confirmed';
            $appointment->confirmed_at = now();
            $appointment->save();

            event(new AppointmentConfirmed($appointment));

            DB::commit();

            return $appointment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel an appointment
     */
    public function cancel(int $appointmentId, ?string $reason = null): Model
    {
        try {
            DB::beginTransaction();

            $appointment = $this->repository->findOrFail($appointmentId);
            $appointment->status = 'cancelled';
            $appointment->cancelled_at = now();
            $appointment->cancellation_reason = $reason;
            $appointment->save();

            event(new AppointmentCancelled($appointment));

            DB::commit();

            return $appointment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Complete an appointment
     */
    public function complete(int $appointmentId): Model
    {
        return $this->update($appointmentId, [
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Reschedule an appointment
     */
    public function reschedule(int $appointmentId, \DateTime $newDateTime, ?int $serviceBayId = null): Model
    {
        $data = [
            'scheduled_datetime' => $newDateTime,
            'status' => 'rescheduled'
        ];

        if ($serviceBayId) {
            $data['service_bay_id'] = $serviceBayId;
        }

        return $this->update($appointmentId, $data);
    }

    /**
     * Get appointments by status
     */
    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Get upcoming appointments for a customer
     */
    public function getUpcomingByCustomer(int $customerId)
    {
        return $this->repository->getUpcomingByCustomer($customerId);
    }

    /**
     * Get appointments for a date range
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->repository->getByDateRange($startDate, $endDate);
    }

    /**
     * Check for scheduling conflicts
     */
    public function hasConflicts(int $serviceBayId, \DateTime $dateTime, ?int $excludeAppointmentId = null): bool
    {
        return $this->repository->checkConflicts($serviceBayId, $dateTime, $excludeAppointmentId);
    }
}
