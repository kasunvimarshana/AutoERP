<?php

namespace App\Modules\Vehicle\Services;

use App\Base\BaseService;
use App\Modules\Vehicle\Repositories\VehicleRepository;
use App\Modules\Vehicle\DTOs\VehicleDTO;
use App\Modules\Vehicle\Events\VehicleCreated;
use App\Modules\Vehicle\Events\VehicleUpdated;
use App\Modules\Vehicle\Events\VehicleDeleted;
use App\Modules\Vehicle\Events\VehicleTransferred;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class VehicleService extends BaseService
{
    public function __construct(VehicleRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createVehicle(VehicleDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $data = $dto->toArray();
            
            $vehicle = $this->repository->create($data);
            
            Event::dispatch(new VehicleCreated($vehicle));
            
            return $vehicle;
        });
    }

    public function updateVehicle(string $id, VehicleDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $vehicle = $this->repository->find($id);
            
            if (!$vehicle) {
                return false;
            }
            
            $data = $dto->toArray();
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $vehicle->refresh();
                Event::dispatch(new VehicleUpdated($vehicle));
            }
            
            return $result;
        });
    }

    public function deleteVehicle(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $vehicle = $this->repository->find($id);
            
            if (!$vehicle) {
                return false;
            }
            
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new VehicleDeleted($vehicle));
            }
            
            return $result;
        });
    }

    public function transferVehicle(string $id, string $newCustomerId): bool
    {
        return $this->executeInTransaction(function () use ($id, $newCustomerId) {
            $vehicle = $this->repository->find($id);
            
            if (!$vehicle) {
                return false;
            }
            
            $oldCustomerId = $vehicle->customer_id;
            
            $result = $this->repository->update($id, [
                'customer_id' => $newCustomerId,
            ]);
            
            if ($result) {
                $vehicle->refresh();
                Event::dispatch(new VehicleTransferred($vehicle, $oldCustomerId, $newCustomerId));
            }
            
            return $result;
        });
    }

    public function updateMileage(string $id, int $mileage): bool
    {
        return $this->executeInTransaction(function () use ($id, $mileage) {
            $vehicle = $this->repository->find($id);
            
            if (!$vehicle) {
                return false;
            }
            
            $result = $this->repository->update($id, [
                'mileage' => $mileage,
            ]);
            
            if ($result) {
                $vehicle->refresh();
                Event::dispatch(new VehicleUpdated($vehicle));
            }
            
            return $result;
        });
    }

    public function getVehicleHistory(string $id): array
    {
        $vehicle = $this->repository->find($id, ['*'], ['serviceHistory', 'customer', 'branch']);
        
        if (!$vehicle) {
            return [];
        }
        
        return [
            'vehicle' => $vehicle,
            'service_history' => $vehicle->serviceHistory,
            'customer' => $vehicle->customer,
            'branch' => $vehicle->branch,
        ];
    }

    public function findByVin(string $vin): ?Model
    {
        return $this->repository->findByVin($vin);
    }

    public function findByRegistration(string $registration): ?Model
    {
        return $this->repository->findByRegistration($registration);
    }

    public function findByCustomer(string $customerId)
    {
        return $this->repository->findByCustomer($customerId);
    }

    public function findByTenant(string $tenantId)
    {
        return $this->repository->findByTenant($tenantId);
    }

    public function findByBranch(string $branchId)
    {
        return $this->repository->findByBranch($branchId);
    }

    public function getDueForService()
    {
        return $this->repository->getDueForService();
    }
}
