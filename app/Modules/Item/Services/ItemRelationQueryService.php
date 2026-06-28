<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemBundle;
use Modules\Item\Models\ItemCode;
use Modules\Item\Models\ItemPrice;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Models\ItemUsageRule;
use Modules\Item\Models\ItemVariant;

final class ItemRelationQueryService
{
    public function units(Item $item, int $perPage): LengthAwarePaginator
    {
        return $item->units()->with('uom')->orderBy('unit_role')->orderBy('id')->paginate($perPage);
    }

    public function variants(Item $item, int $perPage): LengthAwarePaginator
    {
        return $item->variants()->orderBy('name')->paginate($perPage);
    }

    public function bundles(Item $item, int $perPage): LengthAwarePaginator
    {
        return $item->bundleLines()
            ->with(['childItem.category', 'childItem.brand', 'childVariant', 'uom'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function prices(Item $item, int $perPage): LengthAwarePaginator
    {
        return $item->prices()->with(['variant', 'currency', 'uom'])->orderByDesc('effective_from')->orderByDesc('id')->paginate($perPage);
    }

    public function codes(Item $item, int $perPage): LengthAwarePaginator
    {
        return $item->codes()->with('variant')->orderByDesc('is_primary')->orderBy('code')->paginate($perPage);
    }

    public function usageRules(Item $item, int $perPage): LengthAwarePaginator
    {
        return $item->usageRules()->orderBy('module_code')->paginate($perPage);
    }

    public function unit(Item $item, int $id): ItemUnit
    {
        return $this->relation($item, ItemUnit::class, $id);
    }

    public function variant(Item $item, int $id): ItemVariant
    {
        return $this->relation($item, ItemVariant::class, $id);
    }

    public function bundle(Item $item, int $id): ItemBundle
    {
        return ItemBundle::query()->where('parent_item_id', $item->getKey())->findOrFail($id);
    }

    public function price(Item $item, int $id): ItemPrice
    {
        return $this->relation($item, ItemPrice::class, $id);
    }

    public function code(Item $item, int $id): ItemCode
    {
        return $this->relation($item, ItemCode::class, $id);
    }

    public function usageRule(Item $item, int $id): ItemUsageRule
    {
        return $this->relation($item, ItemUsageRule::class, $id);
    }

    private function relation(Item $item, string $model, int $id): Model
    {
        return $model::query()->where('item_id', $item->getKey())->findOrFail($id);
    }
}
