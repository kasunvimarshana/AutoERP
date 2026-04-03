<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListItemData;
use Modules\Pricing\Application\DTOs\UpdatePriceListItemData;
use Modules\Pricing\Infrastructure\Http\Requests\StorePriceListItemRequest;
use Modules\Pricing\Infrastructure\Http\Requests\UpdatePriceListItemRequest;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListItemCollection;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListItemResource;

class PriceListItemController extends AuthorizedController
{
    public function __construct(
        protected FindPriceListItemServiceInterface $findService,
        protected CreatePriceListItemServiceInterface $createService,
        protected UpdatePriceListItemServiceInterface $updateService,
        protected DeletePriceListItemServiceInterface $deleteService,
    ) {}

    public function index(Request $request): PriceListItemCollection
    {
        $filters = $request->only(['tenant_id', 'price_list_id', 'product_id', 'variation_id', 'is_active']);

        return new PriceListItemCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StorePriceListItemRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = PriceListItemData::fromArray([
            'tenantId'       => $v['tenant_id'],
            'priceListId'    => $v['price_list_id'],
            'productId'      => $v['product_id'],
            'variationId'    => $v['variation_id'] ?? null,
            'unitPrice'      => $v['unit_price'],
            'minQuantity'    => $v['min_quantity'],
            'maxQuantity'    => $v['max_quantity'] ?? null,
            'discountPercent'=> $v['discount_percent'],
            'markupPercent'  => $v['markup_percent'],
            'currencyCode'   => $v['currency_code'],
            'uomCode'        => $v['uom_code'] ?? null,
            'isActive'       => $v['is_active'] ?? true,
            'metadata'       => $v['metadata'] ?? null,
        ]);

        $priceListItem = $this->createService->execute($dto->toArray());

        return (new PriceListItemResource($priceListItem))->response()->setStatusCode(201);
    }

    public function show(int $id): PriceListItemResource|JsonResponse
    {
        $priceListItem = $this->findService->find($id);
        if (! $priceListItem) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new PriceListItemResource($priceListItem);
    }

    public function update(UpdatePriceListItemRequest $request, int $id): PriceListItemResource
    {
        $v   = $request->validated();
        $dto = UpdatePriceListItemData::fromArray(array_merge(['id' => $id], [
            'productId'      => $v['product_id'] ?? null,
            'variationId'    => $v['variation_id'] ?? null,
            'unitPrice'      => $v['unit_price'] ?? null,
            'minQuantity'    => $v['min_quantity'] ?? null,
            'maxQuantity'    => $v['max_quantity'] ?? null,
            'discountPercent'=> $v['discount_percent'] ?? null,
            'markupPercent'  => $v['markup_percent'] ?? null,
            'currencyCode'   => $v['currency_code'] ?? null,
            'uomCode'        => $v['uom_code'] ?? null,
            'isActive'       => $v['is_active'] ?? null,
            'metadata'       => $v['metadata'] ?? null,
        ]));

        return new PriceListItemResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Price list item deleted successfully']);
    }
}
