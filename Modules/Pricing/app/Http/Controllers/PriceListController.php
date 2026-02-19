<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pricing\Requests\StorePriceListRequest;
use Modules\Pricing\Requests\UpdatePriceListRequest;
use Modules\Pricing\Resources\PriceListResource;
use Modules\Pricing\Services\PriceListService;

class PriceListController extends Controller
{
    public function __construct(
        private readonly PriceListService $priceListService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $priceLists = $this->priceListService->getAll($filters);

        return $this->successResponse(
            PriceListResource::collection($priceLists),
            'Price lists retrieved successfully'
        );
    }

    public function store(StorePriceListRequest $request): JsonResponse
    {
        $priceList = $this->priceListService->create($request->validated());

        return $this->createdResponse(
            new PriceListResource($priceList),
            'Price list created successfully'
        );
    }

    public function show(int $id): JsonResponse
    {
        $priceList = $this->priceListService->getById($id);

        return $this->successResponse(
            new PriceListResource($priceList),
            'Price list retrieved successfully'
        );
    }

    public function update(UpdatePriceListRequest $request, int $id): JsonResponse
    {
        $priceList = $this->priceListService->update($id, $request->validated());

        return $this->successResponse(
            new PriceListResource($priceList),
            'Price list updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->priceListService->delete($id);

        return $this->successResponse(null, 'Price list deleted successfully');
    }
}
