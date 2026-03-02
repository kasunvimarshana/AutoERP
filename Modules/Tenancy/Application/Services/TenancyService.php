<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Tenancy\Application\DTOs\CreateTenantDTO;
use Modules\Tenancy\Domain\Contracts\TenantRepositoryContract;
use Modules\Tenancy\Domain\Entities\Tenant;

/**
 * Tenancy service.
 *
 * Orchestrates all tenant management use cases.
 * No business logic in controllers — all delegated here.
 */
class TenancyService implements ServiceContract
{
    public function __construct(
        private readonly TenantRepositoryContract $repository,
    ) {}

    /**
     * Return a paginated list of all tenants.
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Return all active tenants.
     */
    public function listActive(): Collection
    {
        return $this->repository->allActive();
    }

    /**
     * Create a new tenant.
     */
    public function create(CreateTenantDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            return $this->repository->create($dto->toArray());
        });
    }

    /**
     * Show a single tenant by ID.
     */
    public function show(int|string $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Find a tenant by its slug.
     */
    public function findBySlug(string $slug): ?Tenant
    {
        return $this->repository->findBySlug($slug);
    }

    /**
     * Find a tenant by its domain.
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return $this->repository->findByDomain($domain);
    }

    /**
     * Update an existing tenant.
     *
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data): Model {
            return $this->repository->update($id, $data);
        });
    }

    /**
     * Delete a tenant.
     */
    public function delete(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->repository->delete($id);
        });
    }
}
