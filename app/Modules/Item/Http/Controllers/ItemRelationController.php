<?php

declare(strict_types=1);

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\Http\Requests\ListItemRequest;
use Modules\Item\Http\Requests\StoreItemBundleRequest;
use Modules\Item\Http\Requests\StoreItemCodeRequest;
use Modules\Item\Http\Requests\StoreItemPriceRequest;
use Modules\Item\Http\Requests\StoreItemUnitRequest;
use Modules\Item\Http\Requests\StoreItemUsageRuleRequest;
use Modules\Item\Http\Requests\StoreItemVariantRequest;
use Modules\Item\Http\Requests\UpdateItemBundleRequest;
use Modules\Item\Http\Requests\UpdateItemCodeRequest;
use Modules\Item\Http\Requests\SupersedeItemPriceRequest;
use Modules\Item\Http\Requests\UpdateItemUnitRequest;
use Modules\Item\Http\Requests\UpdateItemUsageRuleRequest;
use Modules\Item\Http\Requests\UpdateItemVariantRequest;
use Modules\Item\Http\Resources\ItemBundleResource;
use Modules\Item\Http\Resources\ItemCodeResource;
use Modules\Item\Http\Resources\ItemPriceResource;
use Modules\Item\Http\Resources\ItemUnitResource;
use Modules\Item\Http\Resources\ItemUsageRuleResource;
use Modules\Item\Http\Resources\ItemVariantResource;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemBundleService;
use Modules\Item\Services\ItemCodeService;
use Modules\Item\Services\ItemPriceService;
use Modules\Item\Services\ItemQueryService;
use Modules\Item\Services\ItemRelationQueryService;
use Modules\Item\Services\ItemUnitService;
use Modules\Item\Services\ItemUsageRuleService;
use Modules\Item\Services\ItemVariantService;

final class ItemRelationController
{
    public function __construct(
        private readonly ItemQueryService $items,
        private readonly ItemRelationQueryService $queries,
        private readonly ItemUnitService $units,
        private readonly ItemVariantService $variants,
        private readonly ItemBundleService $bundles,
        private readonly ItemPriceService $prices,
        private readonly ItemCodeService $codes,
        private readonly ItemUsageRuleService $usageRules,
        private readonly ItemAuthorizationService $authorization,
    ) {}

    public function units(ListItemRequest $request, int $item): AnonymousResourceCollection
    {
        $this->authorize($request, ItemAuthorizationService::VIEW);

        return ItemUnitResource::collection($this->queries->units($this->item($request, $item), $request->perPage()));
    }

    public function storeUnit(StoreItemUnitRequest $request, int $item): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_UNITS);

        return (new ItemUnitResource($this->units->assign($this->item($request, $item), $request->toData())->load('uom')))
            ->response()->setStatusCode(201);
    }

    public function updateUnit(UpdateItemUnitRequest $request, int $item, int $unit): ItemUnitResource
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_UNITS);
        $parent = $this->item($request, $item);

        return new ItemUnitResource($this->units->update($parent, $this->queries->unit($parent, $unit), $request->toData()));
    }

    public function deleteUnit(ListItemRequest $request, int $item, int $unit): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_UNITS);
        $parent = $this->item($request, $item);
        $this->units->delete($parent, $this->queries->unit($parent, $unit));

        return response()->json(null, 204);
    }

    public function variants(ListItemRequest $request, int $item): AnonymousResourceCollection
    {
        $this->authorize($request, ItemAuthorizationService::VIEW);

        return ItemVariantResource::collection($this->queries->variants(
            $this->item($request, $item),
            $request->validated(),
            $request->perPage(),
        ));
    }

    public function storeVariant(StoreItemVariantRequest $request, int $item): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_VARIANTS);

        return (new ItemVariantResource($this->variants->create($this->item($request, $item), $request->toData())))
            ->response()->setStatusCode(201);
    }

    public function updateVariant(UpdateItemVariantRequest $request, int $item, int $variant): ItemVariantResource
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_VARIANTS);
        $parent = $this->item($request, $item);

        return new ItemVariantResource($this->variants->update($parent, $this->queries->variant($parent, $variant), $request->toData()));
    }

    public function deleteVariant(ListItemRequest $request, int $item, int $variant): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_VARIANTS);
        $parent = $this->item($request, $item);
        $this->variants->delete($parent, $this->queries->variant($parent, $variant));

        return response()->json(null, 204);
    }

    public function bundles(ListItemRequest $request, int $item): AnonymousResourceCollection
    {
        $this->authorize($request, ItemAuthorizationService::VIEW);

        return ItemBundleResource::collection($this->queries->bundles($this->item($request, $item), $request->perPage()));
    }

    public function storeBundle(StoreItemBundleRequest $request, int $item): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_BUNDLES);
        $line = $this->bundles->addLine($this->item($request, $item), $request->toData())
            ->load(['childItem.category', 'childItem.brand', 'childVariant', 'uom']);

        return (new ItemBundleResource($line))->response()->setStatusCode(201);
    }

    public function updateBundle(UpdateItemBundleRequest $request, int $item, int $bundle): ItemBundleResource
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_BUNDLES);
        $parent = $this->item($request, $item);

        return new ItemBundleResource($this->bundles->update($parent, $this->queries->bundle($parent, $bundle), $request->toData()));
    }

    public function deleteBundle(ListItemRequest $request, int $item, int $bundle): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_BUNDLES);
        $parent = $this->item($request, $item);
        $this->bundles->delete($parent, $this->queries->bundle($parent, $bundle));

        return response()->json(null, 204);
    }

    public function prices(ListItemRequest $request, int $item): AnonymousResourceCollection
    {
        $this->authorize($request, ItemAuthorizationService::VIEW);

        return ItemPriceResource::collection($this->queries->prices(
            $this->item($request, $item),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    public function storePrice(StoreItemPriceRequest $request, int $item): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_PRICES);
        $price = $this->prices->create($this->item($request, $item), $request->toData());

        return (new ItemPriceResource($price))->response()->setStatusCode(201);
    }

    public function supersedePrice(SupersedeItemPriceRequest $request, int $item, int $price): ItemPriceResource
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_PRICES);
        $parent = $this->item($request, $item);

        return new ItemPriceResource($this->prices->supersede(
            $parent,
            $this->queries->price($parent, $price),
            $request->toData(),
        ));
    }

    public function codes(ListItemRequest $request, int $item): AnonymousResourceCollection
    {
        $this->authorize($request, ItemAuthorizationService::VIEW);

        return ItemCodeResource::collection($this->queries->codes($this->item($request, $item), $request->perPage()));
    }

    public function storeCode(StoreItemCodeRequest $request, int $item): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_CODES);
        $code = $this->codes->create($this->item($request, $item), $request->toData())->load('variant');

        return (new ItemCodeResource($code))->response()->setStatusCode(201);
    }

    public function updateCode(UpdateItemCodeRequest $request, int $item, int $code): ItemCodeResource
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_CODES);
        $parent = $this->item($request, $item);

        return new ItemCodeResource($this->codes->update($parent, $this->queries->code($parent, $code), $request->toData()));
    }

    public function deleteCode(ListItemRequest $request, int $item, int $code): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_CODES);
        $parent = $this->item($request, $item);
        $this->codes->delete($parent, $this->queries->code($parent, $code));

        return response()->json(null, 204);
    }

    public function usageRules(ListItemRequest $request, int $item): AnonymousResourceCollection
    {
        $this->authorize($request, ItemAuthorizationService::VIEW);

        return ItemUsageRuleResource::collection($this->queries->usageRules($this->item($request, $item), $request->perPage()));
    }

    public function storeUsageRule(StoreItemUsageRuleRequest $request, int $item): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_USAGE_RULES);

        return (new ItemUsageRuleResource($this->usageRules->set($this->item($request, $item), $request->toData())))
            ->response()->setStatusCode(201);
    }

    public function updateUsageRule(UpdateItemUsageRuleRequest $request, int $item, int $rule): ItemUsageRuleResource
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_USAGE_RULES);
        $parent = $this->item($request, $item);

        return new ItemUsageRuleResource($this->usageRules->update($parent, $this->queries->usageRule($parent, $rule), $request->toData()));
    }

    public function deleteUsageRule(ListItemRequest $request, int $item, int $rule): JsonResponse
    {
        $this->authorize($request, ItemAuthorizationService::MANAGE_USAGE_RULES);
        $parent = $this->item($request, $item);
        $this->usageRules->delete($parent, $this->queries->usageRule($parent, $rule));

        return response()->json(null, 204);
    }

    private function item(TenantScopedRequest $request, int $item): Item
    {
        return $this->items->item($item, $request->tenantId(), $request->organizationUnitId());
    }

    private function authorize(TenantScopedRequest $request, string $permission): void
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
    }
}
