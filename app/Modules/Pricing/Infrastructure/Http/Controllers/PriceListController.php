<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Infrastructure\Http\Requests\ListPriceListRequest;
use Modules\Pricing\Infrastructure\Http\Requests\StorePriceListRequest;
use Modules\Pricing\Infrastructure\Http\Requests\UpdatePriceListRequest;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListCollection;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PriceListController extends AuthorizedController
{
    public function __construct(
        protected CreatePriceListServiceInterface $createPriceListService,
        protected UpdatePriceListServiceInterface $updatePriceListService,
        protected DeletePriceListServiceInterface $deletePriceListService,
        protected FindPriceListServiceInterface $findPriceListService,
    ) {}

    public function index(ListPriceListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PriceList::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'type' => $validated['type'] ?? null,
            'currency_id' => $validated['currency_id'] ?? null,
            'is_default' => $validated['is_default'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $priceLists = $this->findPriceListService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new PriceListCollection($priceLists))->response();
    }

    public function store(StorePriceListRequest $request): JsonResponse
    {
        $this->authorize('create', PriceList::class);

        $priceList = $this->createPriceListService->execute($request->validated());

        return (new PriceListResource($priceList))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $priceList): PriceListResource
    {
        $foundPriceList = $this->findPriceListOrFail($priceList);
        $this->authorize('view', $foundPriceList);

        return new PriceListResource($foundPriceList);
    }

    public function update(UpdatePriceListRequest $request, int $priceList): PriceListResource
    {
        $foundPriceList = $this->findPriceListOrFail($priceList);
        $this->authorize('update', $foundPriceList);

        $payload = $request->validated();
        $payload['id'] = $priceList;

        return new PriceListResource($this->updatePriceListService->execute($payload));
    }

    public function destroy(int $priceList): JsonResponse
    {
        $foundPriceList = $this->findPriceListOrFail($priceList);
        $this->authorize('delete', $foundPriceList);

        $this->deletePriceListService->execute(['id' => $priceList]);

        return Response::json(['message' => 'Price list deleted successfully']);
    }

    private function findPriceListOrFail(int $priceListId): PriceList
    {
        $priceList = $this->findPriceListService->find($priceListId);

        if (! $priceList) {
            throw new NotFoundHttpException('Price list not found.');
        }

        return $priceList;
    }
}
