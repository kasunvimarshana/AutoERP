<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Contracts\PriceListServiceInterface;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListResource;

class PriceListController extends Controller
{
    public function __construct(
        private readonly PriceListServiceInterface $priceListService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(PriceListResource::collection($this->priceListService->getAllPriceLists($tenantId)));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $priceList = $this->priceListService->createPriceList($tenantId, $request->all());
        return response()->json(new PriceListResource($priceList), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new PriceListResource($this->priceListService->getPriceList($tenantId, $id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new PriceListResource($this->priceListService->updatePriceList($tenantId, $id, $request->all())));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->priceListService->deletePriceList($request->user()->tenant_id, $id);
        return response()->json(null, 204);
    }
}
