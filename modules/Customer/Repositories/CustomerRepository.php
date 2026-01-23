<?php

namespace Modules\Customer\Repositories;

use App\Repositories\BaseRepository;
use Modules\Customer\Models\Customer;

/**
 * Customer Repository
 *
 * Handles data access for Customer entities.
 * Provides methods for querying and manipulating customer data.
 */
class CustomerRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function model(): string
    {
        return Customer::class;
    }

    /**
     * Find customer by email.
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Find customer by phone.
     */
    public function findByPhone(string $phone): ?Customer
    {
        return $this->findOneBy(['phone' => $phone]);
    }

    /**
     * Search customers by name, email, or phone.
     */
    public function search(string $searchTerm, int $perPage = 15)
    {
        return $this->model
            ->where(function ($query) use ($searchTerm) {
                $query->where('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
            })
            ->paginate($perPage);
    }

    /**
     * Get customers with their vehicles.
     */
    public function getWithVehicles(int $perPage = 15)
    {
        return $this->model
            ->with('vehicles')
            ->paginate($perPage);
    }

    /**
     * Get customers by location.
     */
    public function getByLocation(string $city, ?string $state = null)
    {
        $query = $this->model->where('city', $city);

        if ($state) {
            $query->where('state', $state);
        }

        return $query->get();
    }

    /**
     * Get recently created customers.
     */
    public function getRecentCustomers(int $days = 30, int $limit = 10)
    {
        return $this->model
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customer statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->count(),
            'new_this_month' => $this->count(['created_at' => ['>=', now()->startOfMonth()]]),
            'with_vehicles' => 0, // Would query vehicle relationship
            'active' => 0, // Would query recent activity
        ];
    }
}
