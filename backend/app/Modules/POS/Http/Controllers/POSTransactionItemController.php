<?php

namespace App\Modules\POS\Http\Controllers;

use App\Core\BaseController;
use App\Modules\POS\Services\POSTransactionItemService;
use App\Modules\POS\Requests\CreatePOSTransactionItemRequest;
use App\Modules\POS\Requests\UpdatePOSTransactionItemRequest;
use App\Modules\POS\Resources\POSTransactionItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POSTransactionItemController extends BaseController
{
    protected POSTransactionItemService $service;

    public function __construct(POSTransactionItemService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $items = $this->service->getAllItems();
        return $this->collection(POSTransactionItemResource::collection($items));
    }

    public function store(CreatePOSTransactionItemRequest $request): JsonResponse
    {
        $item = $this->service->createItem($request->validated());
        return $this->created(new POSTransactionItemResource($item));
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->getItem($id);
        if (!$item) {
            return $this->notFound();
        }
        return $this->resource(new POSTransactionItemResource($item));
    }

    public function update(UpdatePOSTransactionItemRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateItem($id, $request->validated());
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'POS Transaction Item updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteItem($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->noContent();
    }

    public function getByTransaction(int $transactionId): JsonResponse
    {
        $items = $this->service->getByTransaction($transactionId);
        return $this->collection(POSTransactionItemResource::collection($items));
    }

    public function getBySKU(int $skuId): JsonResponse
    {
        $items = $this->service->getBySKU($skuId);
        return $this->collection(POSTransactionItemResource::collection($items));
    }

    public function getTopSellingProducts(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $limit = $request->input('limit', 10);
        $products = $this->service->getTopSellingProducts($tenantId, $limit);
        return $this->success(['top_selling_products' => $products]);
    }
}
