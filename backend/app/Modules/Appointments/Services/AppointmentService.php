<?php

namespace App\Modules\Appointments\Services;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Models\ServiceBay;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Appointment::with(['customer', 'vehicle', 'serviceBay'])
            ->latest()
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return Appointment::with(['customer', 'vehicle', 'serviceBay'])->get();
    }

    public function findById(int $id): Appointment
    {
        return Appointment::with(['customer', 'vehicle', 'serviceBay'])->findOrFail($id);
    }

    public function create(array $data): Appointment
    {
        $data['tenant_id'] = auth()->user()->tenant_id ?? 1;
        $data['appointment_number'] = $this->generateAppointmentNumber();
        
        return Appointment::create($data);
    }

    public function update(int $id, array $data): Appointment
    {
        $appointment = $this->findById($id);
        $appointment->update($data);
        
        return $appointment->fresh(['customer', 'vehicle', 'serviceBay']);
    }

    public function delete(int $id): bool
    {
        $appointment = $this->findById($id);
        
        return $appointment->delete();
    }

    public function confirmAppointment(int $id): Appointment
    {
        return DB::transaction(function () use ($id) {
            $appointment = $this->findById($id);
            
            if ($appointment->status !== 'scheduled') {
                throw new \Exception('Only scheduled appointments can be confirmed');
            }
            
            $appointment->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);
            
            return $appointment->fresh(['customer', 'vehicle', 'serviceBay']);
        });
    }

    public function startService(int $id): Appointment
    {
        return DB::transaction(function () use ($id) {
            $appointment = $this->findById($id);
            
            if (!in_array($appointment->status, ['scheduled', 'confirmed'])) {
                throw new \Exception('Only scheduled or confirmed appointments can be started');
            }
            
            $appointment->update([
                'status' => 'in_progress',
            ]);
            
            if ($appointment->service_bay_id) {
                ServiceBay::where('id', $appointment->service_bay_id)->update([
                    'status' => 'occupied',
                    'current_appointment_id' => $appointment->id,
                ]);
            }
            
            return $appointment->fresh(['customer', 'vehicle', 'serviceBay']);
        });
    }

    public function completeAppointment(int $id): Appointment
    {
        return DB::transaction(function () use ($id) {
            $appointment = $this->findById($id);
            
            if ($appointment->status !== 'in_progress') {
                throw new \Exception('Only in-progress appointments can be completed');
            }
            
            $appointment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            
            if ($appointment->service_bay_id) {
                ServiceBay::where('id', $appointment->service_bay_id)->update([
                    'status' => 'available',
                    'current_appointment_id' => null,
                ]);
            }
            
            return $appointment->fresh(['customer', 'vehicle', 'serviceBay']);
        });
    }

    public function cancelAppointment(int $id): Appointment
    {
        return DB::transaction(function () use ($id) {
            $appointment = $this->findById($id);
            
            if (in_array($appointment->status, ['completed', 'cancelled'])) {
                throw new \Exception('Completed or already cancelled appointments cannot be cancelled');
            }
            
            $oldStatus = $appointment->status;
            
            $appointment->update([
                'status' => 'cancelled',
            ]);
            
            if ($appointment->service_bay_id && $oldStatus === 'in_progress') {
                ServiceBay::where('id', $appointment->service_bay_id)->update([
                    'status' => 'available',
                    'current_appointment_id' => null,
                ]);
            }
            
            return $appointment->fresh(['customer', 'vehicle', 'serviceBay']);
        });
    }

    private function generateAppointmentNumber(): string
    {
        $prefix = 'APT';
        $date = now()->format('Ymd');
        $lastAppointment = Appointment::whereDate('created_at', today())
            ->latest('id')
            ->first();
        
        $sequence = $lastAppointment ? ((int) substr($lastAppointment->appointment_number, -4)) + 1 : 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
