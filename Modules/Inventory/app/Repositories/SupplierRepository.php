<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Supplier;

/**
 * Supplier Repository
 *
 * Handles data access for Supplier model
 */
class SupplierRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new Supplier;
    }

    /**
     * Find supplier by supplier code
     */
    public function findBySupplierCode(string $supplierCode): ?Supplier
    {
        /** @var Supplier|null */
        return $this->findOneBy(['supplier_code' => $supplierCode]);
    }

    /**
     * Get active suppliers
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Search suppliers
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): Collection
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    /**
     * Check if supplier code exists
     */
    public function supplierCodeExists(string $supplierCode, ?int $excludeId = null): bool
    {
        $query = $this->model->where('supplier_code', $supplierCode);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
