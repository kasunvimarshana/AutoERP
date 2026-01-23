<?php

declare(strict_types=1);

namespace Modules\Appointment\Services;

use App\Core\Services\BaseService;
use Illuminate\Validation\ValidationException;
use Modules\Appointment\Repositories\BayRepository;

/**
 * Bay Service
 *
 * Contains business logic for Bay operations
 */
class BayService extends BaseService
{
    /**
     * BayService constructor
     */
    public function __construct(BayRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new bay
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        // Validate bay number uniqueness for branch
        if ($this->repository->bayNumberExistsForBranch($data['branch_id'], $data['bay_number'])) {
            throw ValidationException::withMessages([
                'bay_number' => ['The bay number already exists for this branch.'],
            ]);
        }

        return parent::create($data);
    }

    /**
     * Update bay
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        // Validate bay number uniqueness for branch if changed
        if (isset($data['bay_number'], $data['branch_id']) &&
            $this->repository->bayNumberExistsForBranch($data['branch_id'], $data['bay_number'], $id)) {
            throw ValidationException::withMessages([
                'bay_number' => ['The bay number already exists for this branch.'],
            ]);
        }

        return parent::update($id, $data);
    }

    /**
     * Get available bays for a branch
     */
    public function getAvailableForBranch(int $branchId): mixed
    {
        return $this->repository->getAvailableForBranch($branchId);
    }

    /**
     * Get bays by type for a branch
     */
    public function getByTypeForBranch(int $branchId, string $type): mixed
    {
        return $this->repository->getByTypeForBranch($branchId, $type);
    }

    /**
     * Get bay with schedules
     */
    public function getWithSchedules(int $id): mixed
    {
        return $this->repository->findWithSchedules($id);
    }

    /**
     * Get available bays for time range
     */
    public function getAvailableForTimeRange(int $branchId, string $startTime, string $endTime): mixed
    {
        return $this->repository->getAvailableForTimeRange($branchId, $startTime, $endTime);
    }

    /**
     * Change bay status
     */
    public function changeStatus(int $id, string $status): mixed
    {
        if (! in_array($status, ['available', 'occupied', 'maintenance', 'inactive'])) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status value.'],
            ]);
        }

        return $this->update($id, ['status' => $status]);
    }
}
