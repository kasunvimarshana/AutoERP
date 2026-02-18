<?php

namespace App\Modules\Procurement\Repositories;

use App\Modules\Procurement\Models\Supplier;
use App\Repositories\BaseRepository;

/**
 * Supplier Repository
 *
 * Handles data access for suppliers.
 */
class SupplierRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Supplier::class;
    }

    /**
     * Get active suppliers
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveSuppliers()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Search suppliers
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $search)
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->get();
    }

    /**
     * Find supplier by email
     */
    public function findByEmail(string $email): ?Supplier
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find supplier by tax number
     */
    public function findByTaxNumber(string $taxNumber): ?Supplier
    {
        return $this->model->where('tax_number', $taxNumber)->first();
    }
}
