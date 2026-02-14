<?php

namespace App\Repositories;

use App\Models\Tenant;

class TenantRepository
{
    public function all()
    {
        return Tenant::with(['users', 'vendors', 'branches'])->get();
    }

    public function find($id)
    {
        return Tenant::with(['users', 'vendors', 'branches'])->findOrFail($id);
    }

    public function findBySlug($slug)
    {
        return Tenant::where('slug', $slug)->firstOrFail();
    }

    public function create(array $data)
    {
        return Tenant::create($data);
    }

    public function update($id, array $data)
    {
        $tenant = $this->find($id);
        $tenant->update($data);
        return $tenant;
    }

    public function delete($id)
    {
        return Tenant::destroy($id);
    }
}
