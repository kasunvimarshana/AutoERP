<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Pricing\Domain\Entities\PriceRule;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceRuleRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceRuleModel;

class EloquentPriceRuleRepository implements PriceRuleRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?PriceRule
    {
        $model = PriceRuleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return PriceRuleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(PriceRuleModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByPriceList(string $tenantId, string $priceListId): array
    {
        return PriceRuleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('price_list_id', $priceListId)
            ->get()
            ->map(fn(PriceRuleModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(PriceRule $priceRule): void
    {
        $model = PriceRuleModel::withoutGlobalScopes()->findOrNew($priceRule->id);
        $model->fill([
            'tenant_id'        => $priceRule->tenantId,
            'price_list_id'    => $priceRule->priceListId,
            'product_id'       => $priceRule->productId,
            'category_id'      => $priceRule->categoryId,
            'variant_id'       => $priceRule->variantId,
            'min_qty'          => $priceRule->minQty,
            'price'            => $priceRule->price,
            'discount_percent' => $priceRule->discountPercent,
            'start_date'       => $priceRule->startDate,
            'end_date'         => $priceRule->endDate,
        ]);
        if (!$model->exists) {
            $model->id = $priceRule->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        PriceRuleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(PriceRuleModel $model): PriceRule
    {
        return new PriceRule(
            id: $model->id,
            tenantId: $model->tenant_id,
            priceListId: $model->price_list_id,
            productId: $model->product_id,
            categoryId: $model->category_id,
            variantId: $model->variant_id,
            minQty: (float) $model->min_qty,
            price: (float) $model->price,
            discountPercent: (float) $model->discount_percent,
            startDate: $model->start_date,
            endDate: $model->end_date,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
