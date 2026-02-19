<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use App\Core\Services\BaseService;
use Illuminate\Validation\ValidationException;
use Modules\Pricing\Repositories\PriceListRepository;

/**
 * PriceList Service
 *
 * Contains business logic for PriceList operations
 */
class PriceListService extends BaseService
{
    public function __construct(PriceListRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new price list
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        if (isset($data['code']) && $this->repository->codeExists($data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }

        if (! isset($data['currency_code'])) {
            $data['currency_code'] = 'USD';
        }

        if (! isset($data['priority'])) {
            $data['priority'] = 0;
        }

        return parent::create($data);
    }

    /**
     * Update price list
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        if (isset($data['code']) && $this->repository->codeExists($data['code'], $id)) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return parent::update($id, $data);
    }

    /**
     * Get active price lists
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Get default price list
     */
    public function getDefault(): mixed
    {
        return $this->repository->getDefault();
    }

    /**
     * Find best price list for context
     *
     * @param  array<string, mixed>  $context
     */
    public function findBestPriceList(array $context): mixed
    {
        $customerId = $context['customer_id'] ?? null;
        $locationCode = $context['location_code'] ?? null;
        $customerGroup = $context['customer_group'] ?? null;

        // Try customer-specific first
        if ($customerId) {
            $priceList = $this->repository->findActiveForCustomer($customerId);
            if ($priceList) {
                return $priceList;
            }
        }

        // Try location-specific
        if ($locationCode) {
            $priceList = $this->repository->findActiveForLocation($locationCode);
            if ($priceList) {
                return $priceList;
            }
        }

        // Try customer group
        if ($customerGroup) {
            $priceList = $this->repository->findActiveForCustomerGroup($customerGroup);
            if ($priceList) {
                return $priceList;
            }
        }

        // Fallback to default
        return $this->repository->getDefault();
    }

    /**
     * Search price lists
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }

    /**
     * Get price list with items
     */
    public function getWithItems(int $id): mixed
    {
        return $this->repository->findWithItems($id);
    }
}
