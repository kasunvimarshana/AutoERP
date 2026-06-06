<?php

declare(strict_types=1);

namespace Modules\Item\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Item\Http\Requests\ListItemRequest;
use Modules\Item\Http\Requests\StoreItemRequest;
use Modules\Item\Http\Requests\UpdateItemRequest;
use Modules\Item\Http\Resources\ItemBrandResource;
use Modules\Item\Http\Resources\ItemCategoryResource;
use Modules\Item\Http\Resources\ItemResource;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemBrand;
use Modules\Item\Models\ItemCategory;
use Modules\Item\Services\ItemCreationService;
use Modules\Item\Services\ItemUpdateService;

final class ItemController
{
    public function index(ListItemRequest $request): AnonymousResourceCollection
    {
        $query = $this->query($request)->with(['category', 'brand', 'baseUom']);
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%"));
        }
        foreach (['item_type', 'is_stockable', 'is_active'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('category_id')) {
            $query->where('item_category_id', (int) $request->input('category_id'));
        }
        if ($request->filled('brand_id')) {
            $query->where('item_brand_id', (int) $request->input('brand_id'));
        }

        return ItemResource::collection($query
            ->orderBy((string) $request->input('sort', 'name'), (string) $request->input('direction', 'asc'))
            ->paginate($request->perPage()));
    }

    public function store(StoreItemRequest $request, ItemCreationService $service): ItemResource
    {
        return new ItemResource($service->create($request->toData()));
    }

    public function show(ListItemRequest $request, int $item): ItemResource
    {
        return new ItemResource($this->query($request)->with([
            'category', 'brand', 'baseUom', 'units.uom', 'variants',
            'bundleLines.childItem', 'bundleLines.childVariant', 'bundleLines.uom',
            'prices.currency', 'prices.uom', 'codes', 'usageRules',
        ])->findOrFail($item));
    }

    public function update(UpdateItemRequest $request, int $item, ItemUpdateService $service): ItemResource
    {
        $model = Item::query()->forTenant($request->tenantId(), $request->organizationUnitId())->findOrFail($item);

        return new ItemResource($service->update($model, $request->toData()));
    }

    public function lookup(ListItemRequest $request, ?string $kind = null): AnonymousResourceCollection
    {
        $kind = $kind ?? (string) $request->input('kind', 'active');
        $request->merge(match ($kind) {
            'stockable' => ['is_stockable' => true, 'is_active' => true],
            'service' => ['item_type' => 'service', 'is_active' => true],
            'labour' => ['item_type' => 'labour', 'is_active' => true],
            'combo' => ['item_type' => 'combo', 'is_active' => true],
            'package' => ['item_type' => 'package', 'is_active' => true],
            default => ['is_active' => true],
        });

        return $this->index($request);
    }

    public function categories(ListItemRequest $request): AnonymousResourceCollection
    {
        return ItemCategoryResource::collection($this->referenceQuery(ItemCategory::query(), $request)->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get());
    }

    public function brands(ListItemRequest $request): AnonymousResourceCollection
    {
        return ItemBrandResource::collection($this->referenceQuery(ItemBrand::query(), $request)->where('is_active', true)->orderBy('name')->get());
    }

    private function query(ListItemRequest $request): Builder
    {
        return Item::query()->forTenant($request->tenantId(), $request->organizationUnitId());
    }

    private function referenceQuery(Builder $query, ListItemRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());
        if ($request->organizationUnitId() !== null) {
            $query->where(fn (Builder $scope): Builder => $scope->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $request->organizationUnitId()));
        }

        return $query;
    }
}
