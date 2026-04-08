<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Contracts\Repositories\UomConversionRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;

class EloquentUomConversionRepository extends EloquentRepository implements UomConversionRepositoryInterface
{
    public function __construct(UomConversionModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a conversion rule between two UOMs, preferring product-specific rules.
     */
    public function findConversion(
        int $tenantId,
        string $fromUom,
        string $toUom,
        ?string $productId = null,
    ): mixed {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('from_uom', $fromUom)
            ->where('to_uom', $toUom)
            ->where('is_active', true);

        if ($productId !== null) {
            // Prefer product-specific conversion, fall back to global
            $specific = (clone $query)->where('product_id', $productId)->first();
            if ($specific !== null) {
                return $specific;
            }
        }

        return $query->whereNull('product_id')->first();
    }

    /**
     * Return all active conversions involving a given UOM within a tenant.
     */
    public function findByUom(int $tenantId, string $uom): Collection
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($uom) {
                $q->where('from_uom', $uom)->orWhere('to_uom', $uom);
            })
            ->where('is_active', true)
            ->get();
    }
}
