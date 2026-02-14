<?php

namespace App\Services;

use App\Repositories\TenantRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantService
{
    protected $tenantRepository;

    public function __construct(TenantRepository $tenantRepository)
    {
        $this->tenantRepository = $tenantRepository;
    }

    public function getAllTenants()
    {
        return $this->tenantRepository->all();
    }

    public function getTenant($id)
    {
        return $this->tenantRepository->find($id);
    }

    public function createTenant(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Auto-generate slug if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Create tenant
            $tenant = $this->tenantRepository->create($data);

            // Log tenant creation
            \Log::info('Tenant created', ['tenant_id' => $tenant->id]);

            return $tenant;
        });
    }

    public function updateTenant($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $tenant = $this->tenantRepository->update($id, $data);

            // Log tenant update
            \Log::info('Tenant updated', ['tenant_id' => $tenant->id]);

            return $tenant;
        });
    }

    public function deleteTenant($id)
    {
        return DB::transaction(function () use ($id) {
            $result = $this->tenantRepository->delete($id);

            // Log tenant deletion
            \Log::info('Tenant deleted', ['tenant_id' => $id]);

            return $result;
        });
    }
}
