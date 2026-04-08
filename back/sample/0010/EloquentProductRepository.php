<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Persistence\Eloquent\BaseEloquentRepository;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;

/**
 * EloquentProductRepository
 *
 * Infrastructure implementation of ProductRepositoryInterface.
 * Maps between Eloquent ProductModel and pure-PHP Product domain entity.
 * Confirmed from KVAutoERP PR #37.
 */
final class EloquentProductRepository extends BaseEloquentRepository implements ProductRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new ProductModel());
    }

    public function findById(int $id): ?Product
    {
        $model = $this->model->newQuery()->find($id);
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findBySku(string $sku, int $tenantId): ?Product
    {
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('sku', strtoupper($sku))
            ->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 25): mixed
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['track_batches'])) {
            $query->where('track_batches', true);
        }
        if (!empty($filters['sort_by'])) {
            $query->orderBy($filters['sort_by'], $filters['sort_dir'] ?? 'asc');
        } else {
            $query->orderBy('name');
        }

        return $query->paginate($perPage);
    }

    public function save(mixed $entity): Product
    {
        $attrs  = $this->toModelAttributes($entity);
        $model  = $entity->getId()
            ? $this->model->newQuery()->findOrFail($entity->getId())
            : new ProductModel();

        $model->fill($attrs)->save();

        return $this->toDomainEntity($model->fresh());
    }

    public function skuExists(string $sku, int $tenantId, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('sku', strtoupper($sku));
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function findLowStock(int $tenantId, ?int $warehouseId = null): array
    {
        // Products where available qty <= reorder_point
        return \DB::table('products as p')
            ->join('stock_positions as sp', 'sp.product_id', '=', 'p.id')
            ->where('p.tenant_id', $tenantId)
            ->whereNotNull('p.reorder_point')
            ->whereColumn('sp.qty_available', '<=', 'p.reorder_point')
            ->when($warehouseId, fn ($q) => $q->where('sp.warehouse_id', $warehouseId))
            ->select('p.*')
            ->get()
            ->map(fn ($row) => $this->toDomainEntity(ProductModel::find($row->id)))
            ->toArray();
    }

    public function findByType(string $type, int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('status', 'active')
            ->get()
            ->map(fn ($m) => $this->toDomainEntity($m))
            ->toArray();
    }

    public function countByTenant(int $tenantId): int
    {
        return $this->model->newQuery()->where('tenant_id', $tenantId)->count();
    }

    // ── Mapping: Eloquent → Domain Entity ────────────────────────────────────
    protected function toDomainEntity(Model $model): Product
    {
        return new Product(
            tenantId:                  $model->tenant_id,
            sku:                       $model->sku,
            name:                      $model->name,
            price:                     (float) $model->standard_price ?? 0.0,
            currency:                  'USD',
            type:                      $model->type,
            status:                    $model->status,
            description:               $model->description,
            category:                  $model->category_id ? (string) $model->category_id : null,
            unitsOfMeasure:            $model->units_of_measure ?? [],
            trackBatches:              (bool) $model->track_batches,
            trackLots:                 (bool) $model->track_lots,
            trackSerials:              (bool) $model->track_serials,
            trackExpiry:               (bool) $model->track_expiry,
            reorderPoint:              $model->reorder_point ? (float) $model->reorder_point : null,
            safetyStock:               $model->safety_stock ? (float) $model->safety_stock : null,
            leadTimeDays:              $model->lead_time_days,
            standardCost:              $model->standard_cost ? (float) $model->standard_cost : null,
            downloadUrl:               $model->download_url,
            downloadLimit:             $model->download_limit,
            downloadExpiryDays:        $model->download_expiry_days,
            subscriptionInterval:      $model->subscription_interval,
            subscriptionIntervalCount: $model->subscription_interval_count,
            attributes:                $model->attributes,
            metadata:                  $model->metadata,
            id:                        $model->id,
            createdAt:                 $model->created_at?->toDateTimeImmutable(),
            updatedAt:                 $model->updated_at?->toDateTimeImmutable(),
        );
    }

    // ── Mapping: Domain Entity → Eloquent attributes ──────────────────────────
    protected function toModelAttributes(mixed $entity): array
    {
        return [
            'tenant_id'                => $entity->getTenantId()->value(),
            'sku'                      => (string) $entity->getSku(),
            'name'                     => $entity->getName(),
            'description'              => $entity->getDescription(),
            'type'                     => (string) $entity->getType(),
            'status'                   => (string) $entity->getStatus(),
            'standard_price'           => $entity->getPrice()->amount(),
            'standard_cost'            => $entity->getStandardCost(),
            'units_of_measure'         => array_map(fn ($u) => $u->toArray(), $entity->getUnitsOfMeasure()),
            'track_batches'            => $entity->isTrackBatches(),
            'track_lots'               => $entity->isTrackLots(),
            'track_serials'            => $entity->isTrackSerials(),
            'track_expiry'             => $entity->isTrackExpiry(),
            'reorder_point'            => $entity->getReorderPoint(),
            'safety_stock'             => $entity->getSafetyStock(),
            'lead_time_days'           => $entity->getLeadTimeDays(),
            'download_url'             => $entity->getDownloadUrl(),
            'download_limit'           => $entity->getDownloadLimit(),
            'subscription_interval'    => $entity->getSubscriptionInterval(),
            'attributes'               => $entity->getAttributes(),
            'metadata'                 => $entity->getMetadata(),
            'is_variable'              => $entity->getType()->isVariable(),
            'is_composite'             => $entity->getType()->isComposite(),
            'is_kit'                   => $entity->getType()->isKit(),
            'is_stockable'             => $entity->isStockable(),
            'track_inventory'          => $entity->isStockable(),
        ];
    }
}
