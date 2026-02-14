<?php

namespace App\Modules\Appointments\Services;

use App\Modules\Appointments\Models\ServiceBay;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ServiceBayService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return ServiceBay::with('currentAppointment')
            ->latest()
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return ServiceBay::with('currentAppointment')->get();
    }

    public function findById(int $id): ServiceBay
    {
        return ServiceBay::with('currentAppointment')->findOrFail($id);
    }

    public function create(array $data): ServiceBay
    {
        $data['tenant_id'] = auth()->user()->tenant_id ?? 1;
        
        return ServiceBay::create($data);
    }

    public function update(int $id, array $data): ServiceBay
    {
        $serviceBay = $this->findById($id);
        $serviceBay->update($data);
        
        return $serviceBay->fresh('currentAppointment');
    }

    public function delete(int $id): bool
    {
        $serviceBay = $this->findById($id);
        
        return $serviceBay->delete();
    }

    public function getAvailable(): Collection
    {
        return ServiceBay::where('status', 'available')->get();
    }
}
