<?php

declare(strict_types=1);

namespace Modules\Organization\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Organization\Models\Organization;
use Modules\Organization\Repositories\OrganizationRepository;

/**
 * Organization Service
 *
 * Contains business logic for Organization operations
 * Extends BaseService for common service layer functionality
 */
class OrganizationService extends BaseService
{
    /**
     * OrganizationService constructor
     */
    public function __construct(OrganizationRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new organization
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws ServiceException
     */
    public function create(array $data): mixed
    {
        // Validate organization number uniqueness if provided
        if (isset($data['organization_number']) && $this->repository->organizationNumberExists($data['organization_number'])) {
            throw ValidationException::withMessages([
                'organization_number' => ['The organization number has already been taken.'],
            ]);
        }

        // Validate email uniqueness if provided
        if (isset($data['email']) && $this->repository->emailExists($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        // Generate unique organization number if not provided
        if (! isset($data['organization_number'])) {
            $data['organization_number'] = $this->generateUniqueOrganizationNumber();
        }

        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $organization = $this->repository->create($data);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $organization;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to create organization: '.$e->getMessage());
        }
    }

    /**
     * Update an organization
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws ServiceException
     */
    public function update(int $id, array $data): mixed
    {
        // Validate organization number uniqueness if provided and changed
        if (isset($data['organization_number']) && $this->repository->organizationNumberExists($data['organization_number'], $id)) {
            throw ValidationException::withMessages([
                'organization_number' => ['The organization number has already been taken.'],
            ]);
        }

        // Validate email uniqueness if provided and changed
        if (isset($data['email']) && $this->repository->emailExists($data['email'], $id)) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $organization = $this->repository->update($id, $data);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $organization;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to update organization: '.$e->getMessage());
        }
    }

    /**
     * Generate a unique organization number
     */
    protected function generateUniqueOrganizationNumber(): string
    {
        $prefix = 'ORG';
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $number = $prefix.date('Ymd').str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->repository->organizationNumberExists($number);
            $attempt++;
        } while ($exists && $attempt < $maxAttempts);

        if ($exists) {
            throw new ServiceException('Failed to generate unique organization number after '.$maxAttempts.' attempts.');
        }

        return $number;
    }

    /**
     * Get active organizations
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Get organizations with branch count
     */
    public function getWithBranchCount(): mixed
    {
        return $this->repository->getWithBranchCount();
    }

    /**
     * Search organizations
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }

    /**
     * Activate organization
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

            $organization = $this->repository->update($id, ['status' => 'active']);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $organization;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to activate organization: '.$e->getMessage());
        }
    }

    /**
     * Deactivate organization
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

            $organization = $this->repository->update($id, ['status' => 'inactive']);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $organization;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to deactivate organization: '.$e->getMessage());
        }
    }

    /**
     * Suspend organization
     *
     * @throws ServiceException
     */
    public function suspend(int $id): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)

        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $organization = $this->repository->update($id, ['status' => 'suspended']);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $organization;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to suspend organization: '.$e->getMessage());
        }
    }
}
