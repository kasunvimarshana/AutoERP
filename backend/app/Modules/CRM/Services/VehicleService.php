<?php

namespace App\Modules\CRM\Services;

use App\Core\BaseService;
use App\Modules\CRM\Repositories\VehicleRepository;
use App\Modules\CRM\Events\VehicleCreated;
use App\Modules\CRM\Events\VehicleUpdated;

class VehicleService extends BaseService
{
    protected VehicleRepository $repository;

    public function __construct(VehicleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createVehicle(array $data)
    {
        return $this->transaction(function () use ($data) {
            $vehicle = $this->repository->create($data);
            
            $this->logActivity('Vehicle created', ['vehicle_id' => $vehicle->id]);
            
            event(new VehicleCreated($vehicle));
            
            return $vehicle;
        });
    }

    public function updateVehicle(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $vehicle = $this->repository->find($id);
                
                $this->logActivity('Vehicle updated', ['vehicle_id' => $id]);
                
                event(new VehicleUpdated($vehicle));
            }
            
            return $result;
        });
    }

    public function getVehicle(int $id)
    {
        return $this->repository->with(['customer', 'tenant'])->find($id);
    }

    public function getAllVehicles()
    {
        return $this->repository->with(['customer'])->all();
    }

    public function deleteVehicle(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('Vehicle deleted', ['vehicle_id' => $id]);
            }
            
            return $result;
        });
    }
}
