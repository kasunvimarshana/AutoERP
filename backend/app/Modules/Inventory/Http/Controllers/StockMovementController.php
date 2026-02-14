<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\Inventory\Requests\CreateStockMovementRequest;
use App\Modules\Inventory\Resources\StockMovementResource;
use Illuminate\Http\JsonResponse;

/**
 * StockMovement Controller (Append-only ledger)
 * 
 * Handles read and create operations for stock movement management
 * No update or delete operations allowed
 * 
 * @package App\Modules\Inventory\Http\Controllers
 */
class StockMovementController extends BaseController
{
    protected StockMovementService $service;

    public function __construct(StockMovementService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of stock movements
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $stockMovements = $this->service->getAllStockMovements();
        return $this->collection(StockMovementResource::collection($stockMovements));
    }

    /**
     * Store a newly created stock movement
     *
     * @param CreateStockMovementRequest $request
     * @return JsonResponse
     */
    public function store(CreateStockMovementRequest $request): JsonResponse
    {
        $stockMovement = $this->service->createStockMovement($request->validated());
        return $this->created(new StockMovementResource($stockMovement));
    }

    /**
     * Display the specified stock movement
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $stockMovement = $this->service->getStockMovement($id);
        
        if (!$stockMovement) {
            return $this->notFound();
        }
        
        return $this->resource(new StockMovementResource($stockMovement));
    }
}
