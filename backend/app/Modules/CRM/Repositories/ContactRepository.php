<?php

declare(strict_types=1);

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

class ContactRepository extends BaseRepository implements ContactRepositoryInterface
{
    /**
     * ContactRepository constructor.
     */
    public function __construct(Contact $model)
    {
        parent::__construct($model);
    }

    /**
     * Get contacts for a customer
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    /**
     * Get primary contact for a customer
     */
    public function getPrimaryContact(int $customerId): ?Contact
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Get active contacts for a customer
     */
    public function getActiveContactsByCustomer(int $customerId): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Find contact by email
     */
    public function findByEmail(string $email): ?Contact
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get decision makers for a customer
     */
    public function getDecisionMakers(int $customerId): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->where('is_decision_maker', true)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Search contacts
     */
    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->query();

        // Search by name, email, phone
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('mobile', 'like', "%{$query}%")
                  ->orWhere('designation', 'like', "%{$query}%")
                  ->orWhere('department', 'like', "%{$query}%");
            });
        }

        // Apply filters
        if (isset($filters['customer_id'])) {
            $queryBuilder->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['is_primary'])) {
            $queryBuilder->where('is_primary', $filters['is_primary']);
        }

        if (isset($filters['is_decision_maker'])) {
            $queryBuilder->where('is_decision_maker', $filters['is_decision_maker']);
        }

        if (isset($filters['is_active'])) {
            $queryBuilder->where('is_active', $filters['is_active']);
        }

        if (isset($filters['department'])) {
            $queryBuilder->where('department', $filters['department']);
        }

        return $queryBuilder->get();
    }
}
