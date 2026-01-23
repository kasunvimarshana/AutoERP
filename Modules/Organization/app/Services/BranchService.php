<?php

declare(strict_types=1);

namespace Modules\Organization\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Organization\Models\Branch;
use Modules\Organization\Repositories\BranchRepository;

/**
 * Branch Service
 *
 * Contains business logic for Branch operations
 * Extends BaseService for common service layer functionality
 */
class BranchService extends BaseService
{
    /**
     * BranchService constructor
     */
    public function __construct(BranchRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new branch
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws ServiceException
     */
    public function create(array $data): mixed
    {
        // Validate branch code uniqueness if provided
        if (isset($data['branch_code']) && $this->repository->branchCodeExists($data['branch_code'])) {
            throw ValidationException::withMessages([
                'branch_code' => ['The branch code has already been taken.'],
            ]);
        }

        // Generate unique branch code if not provided
        if (! isset($data['branch_code'])) {
            $data['branch_code'] = $this->generateUniqueBranchCode($data['organization_id'] ?? null);
        }

        // Check if we\'re already in a transaction (e.g., from orchestrator or test)


        $shouldManageTransaction = DB::transactionLevel() === 0;



        try {
                if ($shouldManageTransaction) {
                    DB::beginTransaction();
                }

            $branch = $this->repository->create($data);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $branch;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to create branch: '.$e->getMessage());
        }
    }

    /**
     * Update a branch
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws ServiceException
     */
    public function update(int $id, array $data): mixed
    {
        // Validate branch code uniqueness if provided and changed
        if (isset($data['branch_code']) && $this->repository->branchCodeExists($data['branch_code'], $id)) {
            throw ValidationException::withMessages([
                'branch_code' => ['The branch code has already been taken.'],
            ]);
        }

        // Check if we\'re already in a transaction (e.g., from orchestrator or test)


        $shouldManageTransaction = DB::transactionLevel() === 0;



        try {
                if ($shouldManageTransaction) {
                    DB::beginTransaction();
                }

            $branch = $this->repository->update($id, $data);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $branch;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to update branch: '.$e->getMessage());
        }
    }

    /**
     * Generate a unique branch code
     */
    protected function generateUniqueBranchCode(?int $organizationId = null): string
    {
        $prefix = $organizationId ? 'BR'.str_pad((string) $organizationId, 3, '0', STR_PAD_LEFT) : 'BR';
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $number = $prefix.date('ymd').str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
            $exists = $this->repository->branchCodeExists($number);
            $attempt++;
        } while ($exists && $attempt < $maxAttempts);

        if ($exists) {
            throw new ServiceException('Failed to generate unique branch code after '.$maxAttempts.' attempts.');
        }

        return $number;
    }

    /**
     * Get branches by organization
     */
    public function getByOrganization(int $organizationId): mixed
    {
        return $this->repository->getByOrganization($organizationId);
    }

    /**
     * Get active branches
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Get active branches by organization
     */
    public function getActiveByOrganization(int $organizationId): mixed
    {
        return $this->repository->getActiveByOrganization($organizationId);
    }

    /**
     * Search branches
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }

    /**
     * Get nearby branches
     *
     * @param  float  $radius  Radius in kilometers
     */
    public function getNearby(float $latitude, float $longitude, float $radius = 10): mixed
    {
        return $this->repository->getNearby($latitude, $longitude, $radius);
    }

    /**
     * Get branches by city
     */
    public function getByCity(string $city): mixed
    {
        return $this->repository->getByCity($city);
    }

    /**
     * Activate branch
     *
     * @throws ServiceException
     */
    public function activate(int $id): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;


        try {
                if ($shouldManageTransaction) {
                    DB::beginTransaction();
                }

            $branch = $this->repository->update($id, ['status' => 'active']);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $branch;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to activate branch: '.$e->getMessage());
        }
    }

    /**
     * Deactivate branch
     *
     * @throws ServiceException
     */
    public function deactivate(int $id): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;


        try {
                if ($shouldManageTransaction) {
                    DB::beginTransaction();
                }

            $branch = $this->repository->update($id, ['status' => 'inactive']);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $branch;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to deactivate branch: '.$e->getMessage());
        }
    }

    /**
     * Set branch to maintenance mode
     *
     * @throws ServiceException
     */
    public function setMaintenance(int $id): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;


        try {
                if ($shouldManageTransaction) {
                    DB::beginTransaction();
                }

            $branch = $this->repository->update($id, ['status' => 'maintenance']);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $branch;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to set branch to maintenance: '.$e->getMessage());
        }
    }

    /**
     * Check branch capacity availability
     */
    public function checkCapacity(int $branchId, int $currentVehicles): array
    {
        $branch = $this->repository->findOrFail($branchId);

        return [
            'has_capacity' => ! $branch->isAtCapacity($currentVehicles),
            'available_capacity' => $branch->getAvailableCapacity($currentVehicles),
            'total_capacity' => $branch->capacity_vehicles,
            'current_usage' => $currentVehicles,
        ];
    }
}
