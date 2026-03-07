<?php

namespace App\Services;

use App\DTOs\TenantDTO;
use App\Models\Tenant;
use App\Repositories\Interfaces\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function list(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        return $this->tenantRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Tenant
    {
        return $this->tenantRepository->findById($id)
            ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Tenant not found.');
    }

    public function create(array $data): Tenant
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $dto = TenantDTO::fromArray($data);

        return $this->tenantRepository->create($dto->toArray());
    }

    public function update(int $id, array $data): Tenant
    {
        $tenant = $this->findById($id);
        $dto = TenantDTO::fromArray(array_merge([
            'name' => $tenant->name,
            'slug' => $tenant->slug,
        ], $data));

        return $this->tenantRepository->update($tenant, $dto->toArray());
    }

    public function delete(int $id): void
    {
        $tenant = $this->findById($id);
        $this->tenantRepository->delete($tenant);
    }
}
