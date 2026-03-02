<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Organisation\Application\DTOs\CreateBranchDTO;
use Modules\Organisation\Application\DTOs\CreateDepartmentDTO;
use Modules\Organisation\Application\DTOs\CreateLocationDTO;
use Modules\Organisation\Application\DTOs\CreateOrganisationDTO;
use Modules\Organisation\Domain\Contracts\BranchRepositoryContract;
use Modules\Organisation\Domain\Contracts\DepartmentRepositoryContract;
use Modules\Organisation\Domain\Contracts\LocationRepositoryContract;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryContract;

/**
 * Organisation service.
 *
 * Orchestrates all organisation hierarchy use cases:
 * Tenant → Organisation → Branch → Location → Department.
 *
 * All mutations are wrapped in DB::transaction to ensure atomicity.
 * No business logic in controllers — everything is delegated here.
 */
class OrganisationService implements ServiceContract
{
    public function __construct(
        private readonly OrganisationRepositoryContract $repository,
        private readonly BranchRepositoryContract $branchRepository,
        private readonly LocationRepositoryContract $locationRepository,
        private readonly DepartmentRepositoryContract $departmentRepository,
    ) {}

    // -------------------------------------------------------------------------
    // Organisation CRUD
    // -------------------------------------------------------------------------

    /**
     * Return a paginated list of organisations for the current tenant.
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Create a new organisation.
     */
    public function create(CreateOrganisationDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            return $this->repository->create([
                'name'        => $dto->name,
                'code'        => $dto->code,
                'description' => $dto->description,
                'is_active'   => $dto->isActive,
            ]);
        });
    }

    /**
     * Show a single organisation by ID.
     */
    public function show(int|string $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Update an existing organisation.
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
     * Delete an organisation.
     */
    public function delete(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->repository->delete($id);
        });
    }

    // -------------------------------------------------------------------------
    // Branch CRUD
    // -------------------------------------------------------------------------

    /**
     * Return all branches for a given organisation.
     */
    public function listBranches(int|string $organisationId): Collection
    {
        return $this->branchRepository->findByOrganisation($organisationId);
    }

    /**
     * Create a new branch under an organisation.
     */
    public function createBranch(CreateBranchDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            return $this->branchRepository->create([
                'organisation_id' => $dto->organisationId,
                'name'            => $dto->name,
                'code'            => $dto->code,
                'address'         => $dto->address,
                'is_active'       => $dto->isActive,
            ]);
        });
    }

    /**
     * Show a single branch by ID.
     */
    public function showBranch(int|string $id): Model
    {
        return $this->branchRepository->findOrFail($id);
    }

    /**
     * Update an existing branch.
     *
     * @param array<string, mixed> $data
     */
    public function updateBranch(int|string $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data): Model {
            return $this->branchRepository->update($id, $data);
        });
    }

    /**
     * Delete a branch.
     */
    public function deleteBranch(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->branchRepository->delete($id);
        });
    }

    // -------------------------------------------------------------------------
    // Location CRUD
    // -------------------------------------------------------------------------

    /**
     * Return all locations for a given branch.
     */
    public function listLocations(int|string $branchId): Collection
    {
        return $this->locationRepository->findByBranch($branchId);
    }

    /**
     * Create a new location under a branch.
     */
    public function createLocation(CreateLocationDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            return $this->locationRepository->create([
                'branch_id'   => $dto->branchId,
                'name'        => $dto->name,
                'code'        => $dto->code,
                'description' => $dto->description,
                'is_active'   => $dto->isActive,
            ]);
        });
    }

    /**
     * Show a single location by ID.
     */
    public function showLocation(int|string $id): Model
    {
        return $this->locationRepository->findOrFail($id);
    }

    /**
     * Update an existing location.
     *
     * @param array<string, mixed> $data
     */
    public function updateLocation(int|string $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data): Model {
            return $this->locationRepository->update($id, $data);
        });
    }

    /**
     * Delete a location.
     */
    public function deleteLocation(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->locationRepository->delete($id);
        });
    }

    // -------------------------------------------------------------------------
    // Department CRUD
    // -------------------------------------------------------------------------

    /**
     * Return all departments for a given location.
     */
    public function listDepartments(int|string $locationId): Collection
    {
        return $this->departmentRepository->findByLocation($locationId);
    }

    /**
     * Create a new department under a location.
     */
    public function createDepartment(CreateDepartmentDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            return $this->departmentRepository->create([
                'location_id' => $dto->locationId,
                'name'        => $dto->name,
                'code'        => $dto->code,
                'is_active'   => $dto->isActive,
            ]);
        });
    }

    /**
     * Show a single department by ID.
     */
    public function showDepartment(int|string $id): Model
    {
        return $this->departmentRepository->findOrFail($id);
    }

    /**
     * Update an existing department.
     *
     * @param array<string, mixed> $data
     */
    public function updateDepartment(int|string $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data): Model {
            return $this->departmentRepository->update($id, $data);
        });
    }

    /**
     * Delete a department.
     */
    public function deleteDepartment(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->departmentRepository->delete($id);
        });
    }
}
