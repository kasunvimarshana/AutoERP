<?php

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer Address Repository
 * 
 * Handles data access operations for customer addresses
 */
class CustomerAddressRepository extends BaseRepository
{
    /**
     * Specify the model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return CustomerAddress::class;
    }

    /**
     * Get all addresses for a specific customer
     *
     * @param int $customerId
     * @return Collection
     */
    public function findByCustomer(int $customerId): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get default address for a customer
     *
     * @param int $customerId
     * @return CustomerAddress|null
     */
    public function getDefaultAddress(int $customerId): ?CustomerAddress
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get addresses by type
     *
     * @param int $customerId
     * @param string $type
     * @return Collection
     */
    public function findByType(int $customerId, string $type): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->where('type', $type)
            ->get();
    }
}
