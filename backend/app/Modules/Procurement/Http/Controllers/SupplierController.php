<?php

namespace App\Modules\Procurement\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Procurement\Services\SupplierService;
use App\Modules\Procurement\Requests\CreateSupplierRequest;
use App\Modules\Procurement\Requests\UpdateSupplierRequest;
use App\Modules\Procurement\Resources\SupplierResource;
use Illuminate\Http\JsonResponse;

/**
 * Supplier Controller
 * 
 * Handles CRUD operations for supplier management
 * 
 * @package App\Modules\Procurement\Http\Controllers
 */
class SupplierController extends BaseController
{
    protected SupplierService $service;

    public function __construct(SupplierService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of suppliers
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $suppliers = $this->service->getAllSuppliers();
        return $this->collection(SupplierResource::collection($suppliers));
    }

    /**
     * Store a newly created supplier
     *
     * @param CreateSupplierRequest $request
     * @return JsonResponse
     */
    public function store(CreateSupplierRequest $request): JsonResponse
    {
        $supplier = $this->service->createSupplier($request->validated());
        return $this->created(new SupplierResource($supplier));
    }

    /**
     * Display the specified supplier
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $supplier = $this->service->getSupplier($id);
        
        if (!$supplier) {
            return $this->notFound();
        }
        
        return $this->resource(new SupplierResource($supplier));
    }

    /**
     * Update the specified supplier
     *
     * @param UpdateSupplierRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateSupplier($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'Supplier updated successfully');
    }

    /**
     * Remove the specified supplier
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteSupplier($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }
}
