<?php

namespace App\Modules\AppointmentManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\AppointmentManagement\Models\Appointment;

class AppointmentRepository extends BaseRepository
{
    public function __construct(Appointment $model)
    {
        parent::__construct($model);
    }

    /**
     * Search appointments by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('appointment_number', 'like', "%{$search}%")
                    ->orWhere('service_description', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['priority'])) {
            $query->where('priority', $criteria['priority']);
        }

        if (!empty($criteria['appointment_type'])) {
            $query->where('appointment_type', $criteria['appointment_type']);
        }

        if (!empty($criteria['service_bay_id'])) {
            $query->where('service_bay_id', $criteria['service_bay_id']);
        }

        if (!empty($criteria['customer_id'])) {
            $query->where('customer_id', $criteria['customer_id']);
        }

        if (!empty($criteria['vehicle_id'])) {
            $query->where('vehicle_id', $criteria['vehicle_id']);
        }

        if (!empty($criteria['date_from'])) {
            $query->where('scheduled_date', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('scheduled_date', '<=', $criteria['date_to']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['customer', 'vehicle', 'serviceBay'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find appointment by appointment number
     */
    public function findByAppointmentNumber(string $appointmentNumber): ?Appointment
    {
        return $this->model->where('appointment_number', $appointmentNumber)->first();
    }

    /**
     * Get upcoming appointments
     */
    public function getUpcoming()
    {
        return $this->model->upcoming()->with(['customer', 'vehicle', 'serviceBay'])->get();
    }

    /**
     * Get appointments by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->byStatus($status)->with(['customer', 'vehicle'])->get();
    }

    /**
     * Get appointments by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->model->byDateRange($startDate, $endDate)
            ->with(['customer', 'vehicle', 'serviceBay'])
            ->get();
    }

    /**
     * Get appointments for customer
     */
    public function getForCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)
            ->with(['vehicle', 'serviceBay'])
            ->orderBy('scheduled_date', 'desc')
            ->get();
    }

    /**
     * Get appointments for vehicle
     */
    public function getForVehicle(int $vehicleId)
    {
        return $this->model->where('vehicle_id', $vehicleId)
            ->with(['customer', 'serviceBay'])
            ->orderBy('scheduled_date', 'desc')
            ->get();
    }

    /**
     * Get appointments for service bay
     */
    public function getForServiceBay(int $serviceBayId)
    {
        return $this->model->where('service_bay_id', $serviceBayId)
            ->with(['customer', 'vehicle'])
            ->orderBy('scheduled_date')
            ->get();
    }

    /**
     * Get confirmed appointments
     */
    public function getConfirmed()
    {
        return $this->model->confirmed()->with(['customer', 'vehicle', 'serviceBay'])->get();
    }

    /**
     * Get in progress appointments
     */
    public function getInProgress()
    {
        return $this->model->inProgress()->with(['customer', 'vehicle', 'serviceBay'])->get();
    }
}
