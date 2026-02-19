<?php

declare(strict_types=1);

namespace Modules\Customer\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Customer\Models\Customer;

/**
 * Customer Repository
 *
 * Handles data access for Customer model
 * Extends BaseRepository for common CRUD operations
 */
class CustomerRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new Customer;
    }

    /**
     * Find customer by customer number
     */
    public function findByCustomerNumber(string $customerNumber): ?Customer
    {
        /** @var Customer|null */
        return $this->findOneBy(['customer_number' => $customerNumber]);
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        /** @var Customer|null */
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Find customer by phone
     */
    public function findByPhone(string $phone): ?Customer
    {
        /** @var Customer|null */
        return $this->findOneBy(['phone' => $phone]);
    }

    /**
     * Find customer by mobile
     */
    public function findByMobile(string $mobile): ?Customer
    {
        /** @var Customer|null */
        return $this->findOneBy(['mobile' => $mobile]);
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if customer number exists
     */
    public function customerNumberExists(string $customerNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('customer_number', $customerNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get active customers
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()->where('status', 'active')->get();
    }

    /**
     * Get customers by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->newQuery()->where('customer_type', $type)->get();
    }

    /**
     * Get customers with their vehicles
     */
    public function getAllWithVehicles(): Collection
    {
        return $this->model->newQuery()->with('vehicles')->get();
    }

    /**
     * Get customer with vehicles by ID
     */
    public function findWithVehicles(int $id): ?Customer
    {
        /** @var Customer|null */
        return $this->model->newQuery()->with('vehicles')->find($id);
    }

    /**
     * Search customers by name, email, or phone
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%")
                    ->orWhere('company_name', 'like', "%{$query}%");
            })
            ->get();
    }

    /**
     * Get customers due for service follow-up
     */
    public function getDueForFollowUp(int $daysThreshold = 30): Collection
    {
        $thresholdDate = now()->subDays($daysThreshold);

        return $this->model->newQuery()
            ->where('status', 'active')
            ->where('receive_notifications', true)
            ->where(function ($query) use ($thresholdDate) {
                $query->where('last_service_date', '<=', $thresholdDate)
                    ->orWhereNull('last_service_date');
            })
            ->get();
    }
}
