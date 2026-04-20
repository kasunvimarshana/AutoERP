<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Infrastructure\Http\Requests\ListPriceListItemRequest;
use Modules\Pricing\Infrastructure\Http\Requests\StorePriceListItemRequest;
use Modules\Pricing\Infrastructure\Http\Requests\UpdatePriceListItemRequest;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListItemCollection;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListItemResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PriceListItemController extends AuthorizedController
{
    public function __construct(
        protected FindPriceListServiceInterface $findPriceListService,
        protected FindPriceListItemServiceInterface $findPriceListItemService,
        protected CreatePriceListItemServiceInterface $createPriceListItemService,
        protected UpdatePriceListItemServiceInterface $updatePriceListItemService,
        protected DeletePriceListItemServiceInterface $deletePriceListItemService,
    ) {}

    public function index(int $priceList, ListPriceListItemRequest $request): PriceListItemCollection
    {
        $foundPriceList = $this->findPriceListOrFail($priceList);
        $this->authorize('view', $foundPriceList);

        $validated = $request->validated();

        $items = $this->findPriceListItemService->paginateByPriceList(
            tenantId: $foundPriceList->getTenantId(),
            priceListId: $priceList,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return new PriceListItemCollection($items);
    }

    public function store(StorePriceListItemRequest $request, int $priceList): JsonResponse
    {
        $foundPriceList = $this->findPriceListOrFail($priceList);
        $this->authorize('update', $foundPriceList);

        $payload = $request->validated();
        $payload['price_list_id'] = $priceList;

        $item = $this->createPriceListItemService->execute($payload);

        return (new PriceListItemResource($item))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdatePriceListItemRequest $request, int $priceList, int $priceListItem): PriceListItemResource
    {
        $foundPriceList = $this->findPriceListOrFail($priceList);
        $this->authorize('update', $foundPriceList);

        $item = $this->findPriceListItemOrFail($priceListItem, $priceList);

        $payload = $request->validated();
        $payload['id'] = $item->getId();
        $payload['price_list_id'] = $priceList;

        return new PriceListItemResource($this->updatePriceListItemService->execute($payload));
    }

    public function destroy(int $priceList, int $priceListItem): JsonResponse
    {
        $foundPriceList = $this->findPriceListOrFail($priceList);
        $this->authorize('update', $foundPriceList);

        $item = $this->findPriceListItemOrFail($priceListItem, $priceList);
        $this->deletePriceListItemService->execute(['id' => $item->getId()]);

        return Response::json(['message' => 'Price list item deleted successfully']);
    }

    private function findPriceListOrFail(int $priceListId): PriceList
    {
        $priceList = $this->findPriceListService->find($priceListId);

        if (! $priceList) {
            throw new NotFoundHttpException('Price list not found.');
        }

        return $priceList;
    }

    private function findPriceListItemOrFail(int $itemId, int $priceListId): PriceListItem
    {
        $item = $this->findPriceListItemService->find($itemId);
        if (! $item || $item->getPriceListId() !== $priceListId) {
            throw new NotFoundHttpException('Price list item not found.');
        }

        return $item;
    }
}
