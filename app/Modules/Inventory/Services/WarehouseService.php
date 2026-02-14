<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\WarehouseRepository;
use Illuminate\Support\Facades\Log;

class WarehouseService extends BaseService
{
    /**
     * WarehouseService constructor
     */
    public function __construct(WarehouseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get active warehouses
     */
    public function getActive()
    {
        try {
            return $this->repository->getActive();
        } catch (\Exception $e) {
            Log::error('Error fetching active warehouses: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get warehouses by type
     */
    public function getByType(string $type)
    {
        try {
            return $this->repository->getByType($type);
        } catch (\Exception $e) {
            Log::error("Error fetching warehouses by type {$type}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get warehouses by location
     */
    public function getByLocation(string $location)
    {
        try {
            return $this->repository->getByLocation($location);
        } catch (\Exception $e) {
            Log::error("Error fetching warehouses by location {$location}: ".$e->getMessage());
            throw $e;
        }
    }
}
