<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Inventory\Services\BatchService;
use Modules\Inventory\Http\Requests\StoreBatchRequest;
use Modules\Inventory\Http\Requests\UpdateBatchRequest;
use Modules\Inventory\Http\Resources\BatchResource;
use Modules\Inventory\Http\Resources\BatchCollection;

/**
 * Batch Controller
 *
 * Handles HTTP requests for batch management.
 *
 * @OA\Tag(
 *     name="Batches",
 *     description="Batch tracking and expiry management"
 * )
 */
class BatchController extends BaseController
{
    public function __construct(
        protected BatchService $batchService
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of batches
     *
     * @OA\Get(
     *     path="/api/v1/inventory/batches",
     *     summary="List all batches",
     *     tags={"Batches"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="product_id",
     *         in="query",
     *         description="Filter by product ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="expiring_soon",
     *         in="query",
     *         description="Show only batches expiring soon",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->all();
        
        if ($request->has('expiring_soon') && $request->boolean('expiring_soon')) {
            $days = $request->input('days', 30);
            $batches = $this->batchService->getBatchesNearExpiry($days);
        } elseif ($request->has('product_id')) {
            $batches = $this->batchService->getBatchHistory($request->input('product_id'));
        } else {
            $batches = $this->batchService->all($query);
        }

        return $this->successResponse(
            new BatchCollection($batches),
            'Batches retrieved successfully'
        );
    }

    /**
     * Store a newly created batch
     *
     * @OA\Post(
     *     path="/api/v1/inventory/batches",
     *     summary="Create a new batch",
     *     tags={"Batches"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "received_quantity"},
     *             @OA\Property(property="product_id", type="string", format="uuid"),
     *             @OA\Property(property="variant_id", type="string", format="uuid"),
     *             @OA\Property(property="batch_number", type="string"),
     *             @OA\Property(property="lot_number", type="string"),
     *             @OA\Property(property="supplier_id", type="string", format="uuid"),
     *             @OA\Property(property="manufacture_date", type="string", format="date"),
     *             @OA\Property(property="expiry_date", type="string", format="date"),
     *             @OA\Property(property="received_quantity", type="number"),
     *             @OA\Property(property="unit_cost", type="number"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Batch created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreBatchRequest $request): JsonResponse
    {
        $batch = $this->batchService->createBatch($request->validated());

        return $this->successResponse(
            new BatchResource($batch),
            'Batch created successfully',
            201
        );
    }

    /**
     * Display the specified batch
     *
     * @OA\Get(
     *     path="/api/v1/inventory/batches/{id}",
     *     summary="Get batch details",
     *     tags={"Batches"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Batch not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $batch = $this->batchService->findById($id);

        if (!$batch) {
            return $this->errorResponse('Batch not found', 404);
        }

        return $this->successResponse(
            new BatchResource($batch),
            'Batch retrieved successfully'
        );
    }

    /**
     * Update the specified batch
     *
     * @OA\Put(
     *     path="/api/v1/inventory/batches/{id}",
     *     summary="Update batch",
     *     tags={"Batches"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="custom_attributes", type="object")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Batch updated"),
     *     @OA\Response(response=404, description="Batch not found")
     * )
     */
    public function update(UpdateBatchRequest $request, string $id): JsonResponse
    {
        $batch = $this->batchService->update($id, $request->validated());

        if (!$batch) {
            return $this->errorResponse('Batch not found', 404);
        }

        return $this->successResponse(
            new BatchResource($batch),
            'Batch updated successfully'
        );
    }

    /**
     * Get available batches for a product
     *
     * @OA\Get(
     *     path="/api/v1/inventory/products/{productId}/batches/available",
     *     summary="Get available batches for product",
     *     tags={"Batches"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="variant_id",
     *         in="query",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function available(Request $request, string $productId): JsonResponse
    {
        $variantId = $request->input('variant_id');
        $batches = $this->batchService->getAvailableBatches($productId, $variantId);

        return $this->successResponse(
            new BatchCollection($batches),
            'Available batches retrieved successfully'
        );
    }

    /**
     * Get expired batches
     *
     * @OA\Get(
     *     path="/api/v1/inventory/batches/expired",
     *     summary="Get expired batches",
     *     tags={"Batches"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function expired(): JsonResponse
    {
        $batches = $this->batchService->getExpiredBatches();

        return $this->successResponse(
            new BatchCollection($batches),
            'Expired batches retrieved successfully'
        );
    }
}
