<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Pricing\Application\Contracts\ActivatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeactivatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListData;
use Modules\Pricing\Application\DTOs\UpdatePriceListData;
use Modules\Pricing\Infrastructure\Http\Requests\StorePriceListRequest;
use Modules\Pricing\Infrastructure\Http\Requests\UpdatePriceListRequest;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListCollection;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListResource;

class PriceListController extends AuthorizedController
{
    public function __construct(
        protected FindPriceListServiceInterface $findService,
        protected CreatePriceListServiceInterface $createService,
        protected UpdatePriceListServiceInterface $updateService,
        protected DeletePriceListServiceInterface $deleteService,
        protected ActivatePriceListServiceInterface $activateService,
        protected DeactivatePriceListServiceInterface $deactivateService,
    ) {}

    public function index(Request $request): PriceListCollection
    {
        $filters = $request->only(['tenant_id', 'type', 'pricing_method', 'currency_code', 'is_active']);

        return new PriceListCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StorePriceListRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = PriceListData::fromArray([
            'tenantId'      => $v['tenant_id'],
            'name'          => $v['name'],
            'code'          => $v['code'],
            'type'          => $v['type'],
            'pricingMethod' => $v['pricing_method'],
            'currencyCode'  => $v['currency_code'],
            'startDate'     => $v['start_date'] ?? null,
            'endDate'       => $v['end_date'] ?? null,
            'isActive'      => $v['is_active'] ?? true,
            'description'   => $v['description'] ?? null,
            'metadata'      => $v['metadata'] ?? null,
        ]);

        $priceList = $this->createService->execute($dto->toArray());

        return (new PriceListResource($priceList))->response()->setStatusCode(201);
    }

    public function show(int $id): PriceListResource|JsonResponse
    {
        $priceList = $this->findService->find($id);
        if (! $priceList) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new PriceListResource($priceList);
    }

    public function update(UpdatePriceListRequest $request, int $id): PriceListResource
    {
        $v   = $request->validated();
        $dto = UpdatePriceListData::fromArray(array_merge(['id' => $id], [
            'name'          => $v['name'] ?? null,
            'code'          => $v['code'] ?? null,
            'type'          => $v['type'] ?? null,
            'pricingMethod' => $v['pricing_method'] ?? null,
            'currencyCode'  => $v['currency_code'] ?? null,
            'startDate'     => $v['start_date'] ?? null,
            'endDate'       => $v['end_date'] ?? null,
            'isActive'      => $v['is_active'] ?? null,
            'description'   => $v['description'] ?? null,
            'metadata'      => $v['metadata'] ?? null,
        ]));

        return new PriceListResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Price list deleted successfully']);
    }

    public function activate(int $id): PriceListResource
    {
        return new PriceListResource($this->activateService->execute(['id' => $id]));
    }

    public function deactivate(int $id): PriceListResource
    {
        return new PriceListResource($this->deactivateService->execute(['id' => $id]));
    }
}
