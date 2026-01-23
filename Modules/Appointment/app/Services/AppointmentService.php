<?php

declare(strict_types=1);

namespace Modules\Appointment\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Repositories\AppointmentRepository;
use Modules\Appointment\Repositories\BayScheduleRepository;

/**
 * Appointment Service
 *
 * Contains business logic for Appointment operations
 */
class AppointmentService extends BaseService
{
    /**
     * AppointmentService constructor
     */
    public function __construct(
        AppointmentRepository $repository,
        private readonly BayScheduleRepository $bayScheduleRepository
    ) {
        parent::__construct($repository);
    }

    /**
     * Create a new appointment
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException|ServiceException
     */
    public function create(array $data): mixed
    {
        // Generate unique appointment number if not provided
        if (! isset($data['appointment_number'])) {
            $data['appointment_number'] = $this->generateUniqueAppointmentNumber();
        }

        // Check for conflicts
        if ($this->repository->hasConflicts(
            $data['vehicle_id'],
            $data['scheduled_date_time'],
            $data['duration']
        )) {
            throw ValidationException::withMessages([
                'scheduled_date_time' => ['This vehicle already has an appointment at this time.'],
            ]);
        }

        return parent::create($data);
    }

    /**
     * Update appointment
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        // Check for conflicts if time or vehicle changed
        if (isset($data['scheduled_date_time']) || isset($data['duration']) || isset($data['vehicle_id'])) {
            $appointment = $this->repository->findOrFail($id);
            $vehicleId = $data['vehicle_id'] ?? $appointment->vehicle_id;
            $scheduledDateTime = $data['scheduled_date_time'] ?? $appointment->scheduled_date_time;
            $duration = $data['duration'] ?? $appointment->duration;

            if ($this->repository->hasConflicts($vehicleId, $scheduledDateTime, $duration, $id)) {
                throw ValidationException::withMessages([
                    'scheduled_date_time' => ['This vehicle already has an appointment at this time.'],
                ]);
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Get appointment with all relations
     */
    public function getWithRelations(int $id): mixed
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Get appointments by status
     */
    public function getByStatus(string $status): mixed
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Get appointments for a branch
     */
    public function getForBranch(int $branchId): mixed
    {
        return $this->repository->getForBranch($branchId);
    }

    /**
     * Get appointments for a customer
     */
    public function getForCustomer(int $customerId): mixed
    {
        return $this->repository->getForCustomer($customerId);
    }

    /**
     * Get appointments for a vehicle
     */
    public function getForVehicle(int $vehicleId): mixed
    {
        return $this->repository->getForVehicle($vehicleId);
    }

    /**
     * Get appointments in date range
     */
    public function getInDateRange(string $startDate, string $endDate): mixed
    {
        return $this->repository->getInDateRange($startDate, $endDate);
    }

    /**
     * Get upcoming appointments
     */
    public function getUpcoming(): mixed
    {
        return $this->repository->getUpcoming();
    }

    /**
     * Search appointments
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }

    /**
     * Confirm appointment
     */
    public function confirm(int $id): mixed
    {
        return $this->update($id, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Start appointment
     */
    public function start(int $id): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $appointment = $this->update($id, [
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            // Update bay schedules to active
            $this->bayScheduleRepository->getForAppointment($id)->each(function ($schedule) {
                $this->bayScheduleRepository->update($schedule->id, ['status' => 'active']);
            });

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $appointment;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Complete appointment
     */
    public function complete(int $id): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $appointment = $this->update($id, [
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Update bay schedules to completed
            $this->bayScheduleRepository->getForAppointment($id)->each(function ($schedule) {
                $this->bayScheduleRepository->update($schedule->id, ['status' => 'completed']);
            });

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $appointment;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancel appointment
     */
    public function cancel(int $id, ?string $reason = null): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $appointment = $this->update($id, [
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Update bay schedules to cancelled
            $this->bayScheduleRepository->getForAppointment($id)->each(function ($schedule) {
                $this->bayScheduleRepository->update($schedule->id, ['status' => 'cancelled']);
            });

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $appointment;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reschedule appointment
     *
     * @param  array<string, mixed>  $data
     */
    public function reschedule(int $id, array $data): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $appointment = $this->repository->findOrFail($id);

            // Check for conflicts at new time
            if ($this->repository->hasConflicts(
                $appointment->vehicle_id,
                $data['scheduled_date_time'],
                $data['duration'] ?? $appointment->duration,
                $id
            )) {
                throw ValidationException::withMessages([
                    'scheduled_date_time' => ['This vehicle already has an appointment at this time.'],
                ]);
            }

            // Update appointment
            $appointment = $this->update($id, $data);

            // Delete old bay schedules
            $this->bayScheduleRepository->deleteForAppointment($id);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $appointment;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Assign bay to appointment
     *
     * @param  array<string, mixed>  $scheduleData
     */
    public function assignBay(int $id, int $bayId, array $scheduleData = []): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $appointment = $this->repository->findOrFail($id);

            $startTime = $scheduleData['start_time'] ?? $appointment->scheduled_date_time;
            $endTime = $scheduleData['end_time'] ?? date('Y-m-d H:i:s', strtotime($appointment->scheduled_date_time) + ($appointment->duration * 60));

            // Check if bay is available
            if (! $this->bayScheduleRepository->isBayAvailable($bayId, $startTime, $endTime)) {
                throw new ServiceException('Bay is not available for the requested time slot');
            }

            // Create bay schedule
            $this->bayScheduleRepository->create([
                'bay_id' => $bayId,
                'appointment_id' => $id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'scheduled',
                'notes' => $scheduleData['notes'] ?? null,
            ]);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $this->repository->findWithRelations($id);
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Check availability for appointment
     */
    public function checkAvailability(int $branchId, string $startTime, int $duration): array
    {
        $endTime = date('Y-m-d H:i:s', strtotime($startTime) + ($duration * 60));

        $availableBays = DB::table('bays')
            ->where('branch_id', $branchId)
            ->where('status', 'available')
            ->whereNotExists(function ($query) use ($startTime, $endTime) {
                $query->select(DB::raw(1))
                    ->from('bay_schedules')
                    ->whereColumn('bay_schedules.bay_id', 'bays.id')
                    ->whereIn('bay_schedules.status', ['scheduled', 'active'])
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->whereBetween('start_time', [$startTime, $endTime])
                            ->orWhereBetween('end_time', [$startTime, $endTime])
                            ->orWhere(function ($q2) use ($startTime, $endTime) {
                                $q2->where('start_time', '<=', $startTime)
                                    ->where('end_time', '>=', $endTime);
                            });
                    });
            })
            ->get();

        return [
            'available' => $availableBays->count() > 0,
            'available_bays' => $availableBays,
            'requested_time' => $startTime,
            'duration' => $duration,
        ];
    }

    /**
     * Generate unique appointment number
     */
    protected function generateUniqueAppointmentNumber(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $appointmentNumber = Appointment::generateAppointmentNumber();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new ServiceException('Failed to generate unique appointment number after maximum attempts');
            }
        } while ($this->repository->appointmentNumberExists($appointmentNumber));

        return $appointmentNumber;
    }
}
