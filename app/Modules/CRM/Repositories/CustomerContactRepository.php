<?php

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\CustomerContact;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer Contact Repository
 * 
 * Handles data access operations for customer contacts
 */
class CustomerContactRepository extends BaseRepository
{
    /**
     * Specify the model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return CustomerContact::class;
    }

    /**
     * Get all contacts for a specific customer
     *
     * @param int $customerId
     * @return Collection
     */
    public function findByCustomer(int $customerId): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get primary contact for a customer
     *
     * @param int $customerId
     * @return CustomerContact|null
     */
    public function getPrimaryContact(int $customerId): ?CustomerContact
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Find contact by email
     *
     * @param string $email
     * @return CustomerContact|null
     */
    public function findByEmail(string $email): ?CustomerContact
    {
        return $this->model->where('email', $email)->first();
    }
}
