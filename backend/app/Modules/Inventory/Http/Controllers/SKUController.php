<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Inventory\Services\SKUService;
use App\Modules\Inventory\Requests\CreateSKURequest;
use App\Modules\Inventory\Requests\UpdateSKURequest;
use App\Modules\Inventory\Resources\SKUResource;
use Illuminate\Http\JsonResponse;

/**
 * SKU Controller
 * 
 * Handles CRUD operations for SKU management
 * 
 * @package App\Modules\Inventory\Http\Controllers
 */
class SKUController extends BaseController
{
    protected SKUService $service;

    public function __construct(SKUService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of SKUs
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $skus = $this->service->getAllSKUs();
        return $this->collection(SKUResource::collection($skus));
    }

    /**
     * Store a newly created SKU
     *
     * @param CreateSKURequest $request
     * @return JsonResponse
     */
    public function store(CreateSKURequest $request): JsonResponse
    {
        $sku = $this->service->createSKU($request->validated());
        return $this->created(new SKUResource($sku));
    }

    /**
     * Display the specified SKU
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $sku = $this->service->getSKU($id);
        
        if (!$sku) {
            return $this->notFound();
        }
        
        return $this->resource(new SKUResource($sku));
    }

    /**
     * Update the specified SKU
     *
     * @param UpdateSKURequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateSKURequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateSKU($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'SKU updated successfully');
    }

    /**
     * Remove the specified SKU
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteSKU($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }
}
