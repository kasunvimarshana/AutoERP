<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Persistence\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * ProductRepository
 *
 * Concrete product data-access layer.  Inherits all dynamic CRUD,
 * pagination, filtering, and search from BaseRepository.
 */
class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Domain-specific methods
    // ─────────────────────────────────────────────────────────────────────────

    /** {@inheritDoc} */
    public function searchForTenant(string $term, string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($term): void {
                $q->where('name',    'LIKE', "%{$term}%")
                  ->orWhere('code',     'LIKE', "%{$term}%")
                  ->orWhere('sku',      'LIKE', "%{$term}%")
                  ->orWhere('barcode',  'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%")
                  ->orWhereHas('category', fn ($cq) => $cq->where('name', 'LIKE', "%{$term}%"));
            })
            ->with('category')
            ->paginate($perPage);
    }

    /** {@inheritDoc} */
    public function listForTenant(
        string $tenantId,
        array  $filters   = [],
        int    $perPage   = 15,
        array  $relations = ['category'],
        array  $orderBy   = ['created_at' => 'desc']
    ): LengthAwarePaginator {
        $filters['tenant_id'] = $tenantId;

        return $this->paginate($filters, $perPage, ['*'], 'page', null, $relations, $orderBy);
    }

    /** {@inheritDoc} */
    public function findByCode(string $code, string $tenantId): ?Product
    {
        /** @var Product|null */
        return $this->findBy(['code' => $code, 'tenant_id' => $tenantId]);
    }

    /** {@inheritDoc} */
    public function findBySku(string $sku, string $tenantId): ?Product
    {
        /** @var Product|null */
        return $this->findBy(['sku' => $sku, 'tenant_id' => $tenantId]);
    }

    /** {@inheritDoc} */
    public function listByCategory(string $categoryId, string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginate(
            filters:   ['category_id' => $categoryId, 'tenant_id' => $tenantId],
            perPage:   $perPage,
            relations: ['category']
        );
    }
}
