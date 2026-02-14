<?php

namespace App\Modules\Vendor\Services;

use App\Core\Services\BaseService;
use App\Modules\Vendor\Repositories\VendorRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VendorService extends BaseService
{
    /**
     * VendorService constructor
     */
    public function __construct(VendorRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Activate vendor
     */
    public function activate(int $id): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->repository->update($id, ['is_active' => true]);
            DB::commit();

            Log::info("Vendor {$id} activated successfully");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error activating vendor {$id}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Deactivate vendor
     */
    public function deactivate(int $id): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->repository->update($id, ['is_active' => false]);
            DB::commit();

            Log::info("Vendor {$id} deactivated successfully");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deactivating vendor {$id}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get active vendors
     */
    public function getActive()
    {
        try {
            return $this->repository->getActive();
        } catch (\Exception $e) {
            Log::error('Error fetching active vendors: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get vendors by type
     */
    public function getByType(string $type)
    {
        try {
            return $this->repository->getByType($type);
        } catch (\Exception $e) {
            Log::error("Error fetching vendors by type {$type}: ".$e->getMessage());
            throw $e;
        }
    }
}
