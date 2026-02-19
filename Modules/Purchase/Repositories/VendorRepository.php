<?php

declare(strict_types=1);

namespace Modules\Purchase\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Purchase\Enums\VendorStatus;
use Modules\Purchase\Exceptions\VendorNotFoundException;
use Modules\Purchase\Models\Vendor;

/**
 * Vendor Repository
 *
 * Handles data access operations for vendors.
 */
class VendorRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    protected function makeModel(): Model
    {
        return new Vendor;
    }

    /**
     * Find vendor by code.
     */
    public function findByCode(string $code): ?Vendor
    {
        return $this->model->where('vendor_code', $code)->first();
    }

    /**
     * Find vendor by code or fail.
     */
    public function findByCodeOrFail(string $code): Vendor
    {
        $vendor = $this->findByCode($code);

        if (! $vendor) {
            throw new VendorNotFoundException("Vendor with code {$code} not found");
        }

        return $vendor;
    }

    /**
     * Find vendor by email.
     */
    public function findByEmail(string $email): ?Vendor
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Search vendors with filters.
     */
    public function searchVendors(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('vendor_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (isset($filters['has_credit_limit'])) {
            if ($filters['has_credit_limit']) {
                $query->whereNotNull('credit_limit');
            } else {
                $query->whereNull('credit_limit');
            }
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get active vendors.
     */
    public function getActiveVendors(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', VendorStatus::ACTIVE)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get vendors with outstanding balance.
     */
    public function getVendorsWithOutstandingBalance(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', VendorStatus::ACTIVE)
            ->whereRaw('current_balance > ?', [0])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Update vendor and return the updated model.
     */
    public function update(int|string $id, array $data): Vendor
    {
        $vendor = $this->findOrFail($id);
        $vendor->update($data);

        return $vendor->fresh();
    }
}
