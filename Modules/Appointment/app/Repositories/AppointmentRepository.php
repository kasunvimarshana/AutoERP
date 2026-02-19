<?php

declare(strict_types=1);

namespace Modules\Appointment\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Appointment\Models\Appointment;

/**
 * Appointment Repository
 *
 * Handles data access for Appointment model
 */
class AppointmentRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new Appointment;
    }

    /**
     * Find appointment by appointment number
     */
    public function findByAppointmentNumber(string $appointmentNumber): ?Appointment
    {
        /** @var Appointment|null */
        return $this->findOneBy(['appointment_number' => $appointmentNumber]);
    }

    /**
     * Check if appointment number exists
     */
    public function appointmentNumberExists(string $appointmentNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('appointment_number', $appointmentNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get appointments by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()->where('status', $status)->get();
    }

    /**
     * Get appointments for a branch
     */
    public function getForBranch(int $branchId): Collection
    {
        return $this->model->newQuery()->where('branch_id', $branchId)->get();
    }

    /**
     * Get appointments for a customer
     */
    public function getForCustomer(int $customerId): Collection
    {
        return $this->model->newQuery()->where('customer_id', $customerId)->get();
    }

    /**
     * Get appointments for a vehicle
     */
    public function getForVehicle(int $vehicleId): Collection
    {
        return $this->model->newQuery()->where('vehicle_id', $vehicleId)->get();
    }

    /**
     * Get appointments in date range
     */
    public function getInDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->newQuery()
            ->whereBetween('scheduled_date_time', [$startDate, $endDate])
            ->orderBy('scheduled_date_time')
            ->get();
    }

    /**
     * Get upcoming appointments
     */
    public function getUpcoming(): Collection
    {
        return $this->model->newQuery()
            ->where('scheduled_date_time', '>=', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('scheduled_date_time')
            ->get();
    }

    /**
     * Get appointment with all relations
     */
    public function findWithRelations(int $id): ?Appointment
    {
        /** @var Appointment|null */
        return $this->model->newQuery()
            ->with(['customer', 'vehicle', 'branch', 'assignedTechnician', 'baySchedules.bay'])
            ->find($id);
    }

    /**
     * Check for conflicting appointments
     */
    public function hasConflicts(int $vehicleId, string|\DateTimeInterface $scheduledDateTime, int $duration, ?int $excludeId = null): bool
    {
        // Convert Carbon/DateTime to string if necessary
        if ($scheduledDateTime instanceof \DateTimeInterface) {
            $scheduledDateTime = $scheduledDateTime->format('Y-m-d H:i:s');
        }

        $endTime = date('Y-m-d H:i:s', strtotime($scheduledDateTime) + ($duration * 60));

        // Get all appointments for this vehicle in the relevant time window
        $query = $this->model->newQuery()
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Get appointments and check for conflicts in PHP to be database-agnostic
        $appointments = $query->get();

        $newStart = strtotime($scheduledDateTime);
        $newEnd = strtotime($endTime);

        foreach ($appointments as $appointment) {
            $existingStart = strtotime($appointment->scheduled_date_time);
            $existingEnd = $existingStart + ($appointment->duration * 60);

            // Check if time ranges overlap
            if ($newStart < $existingEnd && $newEnd > $existingStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get appointments for technician
     */
    public function getForTechnician(int $technicianId): Collection
    {
        return $this->model->newQuery()
            ->where('assigned_technician_id', $technicianId)
            ->orderBy('scheduled_date_time')
            ->get();
    }

    /**
     * Search appointments
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('appointment_number', 'like', "%{$query}%")
                    ->orWhereHas('customer', function ($q2) use ($query) {
                        $q2->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->orWhereHas('vehicle', function ($q2) use ($query) {
                        $q2->where('license_plate', 'like', "%{$query}%")
                            ->orWhere('vin', 'like', "%{$query}%");
                    });
            })
            ->with(['customer', 'vehicle', 'branch'])
            ->get();
    }
}
