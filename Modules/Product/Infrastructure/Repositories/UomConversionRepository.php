<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Product\Domain\Contracts\UomConversionRepositoryInterface;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Infrastructure\Models\UomConversionModel;

class UomConversionRepository extends BaseRepository implements UomConversionRepositoryInterface
{
    protected function model(): string
    {
        return UomConversionModel::class;
    }

    /**
     * {@inheritDoc}
     */
    public function findByProduct(int $productId, int $tenantId): array
    {
        return $this->newQuery()
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn (UomConversionModel $m) => $this->toDomain($m))
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function save(UomConversion $conversion): UomConversion
    {
        if ($conversion->id !== null) {
            $model = $this->newQuery()
                ->where('id', $conversion->id)
                ->firstOrFail();
        } else {
            $model = new UomConversionModel;
        }

        $model->product_id = $conversion->productId;
        $model->tenant_id = $conversion->tenantId;
        $model->from_uom = $conversion->fromUom;
        $model->to_uom = $conversion->toUom;
        $model->factor = $conversion->factor;
        $model->save();

        return $this->toDomain($model);
    }

    /**
     * {@inheritDoc}
     *
     * Replaces all existing conversions for a product by deleting then re-inserting.
     * This ensures the stored set is always exactly what the caller provides.
     */
    public function replaceForProduct(int $productId, int $tenantId, array $conversions): void
    {
        $this->newQuery()
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->delete();

        foreach ($conversions as $conversion) {
            $this->save($conversion);
        }
    }

    /**
     * {@inheritDoc}
     *
     * Converts a quantity using the stored factor.
     *
     * When fromUom === toUom, the original quantity is returned unchanged.
     * Otherwise, a direct conversion factor is looked up. If none exists the
     * inverse factor is tried (to_uom → from_uom, inverted). Returns null when
     * no path can be found.
     *
     * All arithmetic uses BCMath to maintain financial precision.
     */
    public function convert(int $productId, int $tenantId, string $quantity, string $fromUom, string $toUom): ?string
    {
        if ($fromUom === $toUom) {
            return bcadd($quantity, '0', 4);
        }

        // Direct path: fromUom → toUom
        $direct = $this->newQuery()
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->where('from_uom', $fromUom)
            ->where('to_uom', $toUom)
            ->first();

        if ($direct !== null) {
            return bcmul($quantity, (string) $direct->factor, 4);
        }

        // Inverse path: toUom → fromUom (factor is the denominator)
        $inverse = $this->newQuery()
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->where('from_uom', $toUom)
            ->where('to_uom', $fromUom)
            ->first();

        if ($inverse !== null && bccomp((string) $inverse->factor, '0', 4) !== 0) {
            return bcdiv($quantity, (string) $inverse->factor, 4);
        }

        return null;
    }

    private function toDomain(UomConversionModel $model): UomConversion
    {
        return new UomConversion(
            id: $model->id,
            productId: $model->product_id,
            tenantId: $model->tenant_id,
            fromUom: $model->from_uom,
            toUom: $model->to_uom,
            factor: bcadd((string) $model->factor, '0', 4),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
