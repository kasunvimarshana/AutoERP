<?php

namespace App\Modules\CRM\Repositories;

use App\Modules\CRM\Models\Customer;
use App\Repositories\BaseRepository;

class CustomerRepository extends BaseRepository
{
    protected function model(): string
    {
        return Customer::class;
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->query()->where('email', $email)->first();
    }

    /**
     * Get active customers
     */
    public function getActive(int $perPage = 15)
    {
        return $this->query()->active()->paginate($perPage);
    }

    /**
     * Get customers by type
     */
    public function getByType(string $type, int $perPage = 15)
    {
        return $this->query()->ofType($type)->paginate($perPage);
    }

    /**
     * Search customers
     */
    public function search(string $term, int $perPage = 15)
    {
        return $this->query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('company_name', 'like', "%{$term}%");
            })
            ->paginate($perPage);
    }
}
