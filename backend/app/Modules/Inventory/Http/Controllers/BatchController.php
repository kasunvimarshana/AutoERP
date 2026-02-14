<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Inventory\Services\BatchService;
use App\Modules\Inventory\Requests\CreateBatchRequest;
use App\Modules\Inventory\Requests\UpdateBatchRequest;
use App\Modules\Inventory\Resources\BatchResource;
use Illuminate\Http\JsonResponse;

/**
 * Batch Controller
 * 
 * Handles CRUD operations for batch management
 * 
 * @package App\Modules\Inventory\Http\Controllers
 */
class BatchController extends BaseController
{
    protected BatchService $service;

    public function __construct(BatchService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of batches
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $batches = $this->service->getAllBatches();
        return $this->collection(BatchResource::collection($batches));
    }

    /**
     * Store a newly created batch
     *
     * @param CreateBatchRequest $request
     * @return JsonResponse
     */
    public function store(CreateBatchRequest $request): JsonResponse
    {
        $batch = $this->service->createBatch($request->validated());
        return $this->created(new BatchResource($batch));
    }

    /**
     * Display the specified batch
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $batch = $this->service->getBatch($id);
        
        if (!$batch) {
            return $this->notFound();
        }
        
        return $this->resource(new BatchResource($batch));
    }

    /**
     * Update the specified batch
     *
     * @param UpdateBatchRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateBatchRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateBatch($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'Batch updated successfully');
    }

    /**
     * Remove the specified batch
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteBatch($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }
}
