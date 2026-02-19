<?php

namespace App\Services;

use App\Contracts\Services\TenantServiceInterface;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantService implements TenantServiceInterface
{
    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] ??= Str::slug($data['name']);
            $data['status'] ??= TenantStatus::Trial;

            return Tenant::create($data);
        });
    }

    public function update(string $id, array $data): Tenant
    {
        return DB::transaction(function () use ($id, $data) {
            $tenant = Tenant::findOrFail($id);
            $tenant->update($data);

            return $tenant->fresh();
        });
    }

    public function suspend(string $id): Tenant
    {
        return DB::transaction(function () use ($id) {
            $tenant = Tenant::findOrFail($id);
            $tenant->update([
                'status' => TenantStatus::Suspended,
                'suspended_at' => now(),
            ]);

            return $tenant->fresh();
        });
    }

    public function activate(string $id): Tenant
    {
        return DB::transaction(function () use ($id) {
            $tenant = Tenant::findOrFail($id);
            $tenant->update([
                'status' => TenantStatus::Active,
                'suspended_at' => null,
            ]);

            return $tenant->fresh();
        });
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Tenant::orderBy('name')->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)->first();
    }
}
