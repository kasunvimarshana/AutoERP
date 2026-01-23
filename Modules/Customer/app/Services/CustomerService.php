<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Customer\Models\Customer;
use Modules\Customer\Repositories\CustomerRepository;

/**
 * Customer Service
 *
 * Contains business logic for Customer operations
 * Extends BaseService for common service layer functionality
 */
class CustomerService extends BaseService
{
    /**
     * CustomerService constructor
     */
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new customer
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        // Validate email uniqueness if provided
        if (isset($data['email']) && $this->repository->emailExists($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        // Generate unique customer number if not provided
        if (! isset($data['customer_number'])) {
            $data['customer_number'] = $this->generateUniqueCustomerNumber();
        }

        return parent::create($data);
    }

    /**
     * Update customer
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        // Validate email uniqueness if provided
        if (isset($data['email']) && $this->repository->emailExists($data['email'], $id)) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        return parent::update($id, $data);
    }

    /**
     * Get customer with vehicles
     */
    public function getWithVehicles(int $id): mixed
    {
        return $this->repository->findWithVehicles($id);
    }

    /**
     * Search customers
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }

    /**
     * Get customers by type
     */
    public function getByType(string $type): mixed
    {
        return $this->repository->getByType($type);
    }

    /**
     * Get active customers
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Get customers due for follow-up
     */
    public function getDueForFollowUp(int $daysThreshold = 30): mixed
    {
        return $this->repository->getDueForFollowUp($daysThreshold);
    }

    /**
     * Update customer last service date
     */
    public function updateLastServiceDate(int $id, string $date): mixed
    {
        return $this->update($id, ['last_service_date' => $date]);
    }

    /**
     * Change customer status
     */
    public function changeStatus(int $id, string $status): mixed
    {
        if (! in_array($status, ['active', 'inactive', 'blocked'])) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status value.'],
            ]);
        }

        return $this->update($id, ['status' => $status]);
    }

    /**
     * Generate unique customer number
     */
    protected function generateUniqueCustomerNumber(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $customerNumber = Customer::generateCustomerNumber();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new ServiceException('Failed to generate unique customer number after maximum attempts');
            }
        } while ($this->repository->customerNumberExists($customerNumber));

        return $customerNumber;
    }

    /**
     * Get customer statistics
     */
    public function getStatistics(int $customerId): array
    {
        $customer = $this->repository->findWithVehicles($customerId);

        if (! $customer) {
            throw new ServiceException('Customer not found');
        }

        return [
            'total_vehicles' => $customer->vehicles->count(),
            'active_vehicles' => $customer->vehicles->where('status', 'active')->count(),
            'total_service_records' => $customer->serviceRecords->count(),
            'last_service_date' => $customer->last_service_date?->format('Y-m-d'),
        ];
    }

    /**
     * Merge duplicate customers
     *
     * Merges source customer into target customer and transfers all vehicles
     */
    public function mergeDuplicates(int $targetId, int $sourceId): mixed
    {
        try {
            DB::beginTransaction();

            $target = $this->repository->findOrFail($targetId);
            $source = $this->repository->findOrFail($sourceId);

            // Transfer vehicles from source to target
            $source->vehicles()->update(['customer_id' => $targetId]);

            // Transfer service records
            $source->serviceRecords()->update(['customer_id' => $targetId]);

            // Update target customer's last service date if source is more recent
            if ($source->last_service_date &&
                (! $target->last_service_date || $source->last_service_date > $target->last_service_date)) {
                $target->last_service_date = $source->last_service_date;
                $target->save();
            }

            // Soft delete the source customer
            $this->repository->delete($sourceId);

            DB::commit();

            return $target->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
