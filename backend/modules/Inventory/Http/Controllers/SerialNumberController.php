<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Inventory\Services\SerialNumberService;
use Modules\Inventory\Http\Requests\StoreSerialNumberRequest;
use Modules\Inventory\Http\Requests\BulkRegisterSerialNumbersRequest;
use Modules\Inventory\Http\Requests\AllocateSerialNumberRequest;
use Modules\Inventory\Http\Resources\SerialNumberResource;
use Modules\Inventory\Http\Resources\SerialNumberCollection;

/**
 * Serial Number Controller
 *
 * Handles HTTP requests for serial number management.
 *
 * @OA\Tag(
 *     name="Serial Numbers",
 *     description="Serial number tracking and warranty management"
 * )
 */
class SerialNumberController extends BaseController
{
    public function __construct(
        protected SerialNumberService $serialNumberService
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of serial numbers
     *
     * @OA\Get(
     *     path="/api/v1/inventory/serial-numbers",
     *     summary="List serial numbers",
     *     tags={"Serial Numbers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="product_id",
     *         in="query",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         @OA\Schema(type="string", enum={"in_stock", "sold", "defective"})
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->all();
        
        if ($request->has('product_id')) {
            $serialNumbers = $this->serialNumberService->getSerialNumberHistory(
                $request->input('product_id')
            );
        } else {
            $serialNumbers = $this->serialNumberService->all($query);
        }

        return $this->successResponse(
            new SerialNumberCollection($serialNumbers),
            'Serial numbers retrieved successfully'
        );
    }

    /**
     * Register a new serial number
     *
     * @OA\Post(
     *     path="/api/v1/inventory/serial-numbers",
     *     summary="Register a new serial number",
     *     tags={"Serial Numbers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "serial_number"},
     *             @OA\Property(property="product_id", type="string", format="uuid"),
     *             @OA\Property(property="variant_id", type="string", format="uuid"),
     *             @OA\Property(property="batch_id", type="string", format="uuid"),
     *             @OA\Property(property="serial_number", type="string"),
     *             @OA\Property(property="warehouse_id", type="string", format="uuid"),
     *             @OA\Property(property="location_id", type="string", format="uuid"),
     *             @OA\Property(property="purchase_cost", type="number"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Serial number registered"),
     *     @OA\Response(response=409, description="Duplicate serial number")
     * )
     */
    public function store(StoreSerialNumberRequest $request): JsonResponse
    {
        try {
            $serialNumber = $this->serialNumberService->registerSerialNumber(
                $request->validated()
            );

            return $this->successResponse(
                new SerialNumberResource($serialNumber),
                'Serial number registered successfully',
                201
            );
        } catch (\Modules\Inventory\Exceptions\DuplicateSerialNumberException $e) {
            return $this->errorResponse($e->getMessage(), 409);
        }
    }

    /**
     * Bulk register serial numbers
     *
     * @OA\Post(
     *     path="/api/v1/inventory/serial-numbers/bulk",
     *     summary="Bulk register serial numbers",
     *     tags={"Serial Numbers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"serial_numbers"},
     *             @OA\Property(
     *                 property="serial_numbers",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="string"),
     *                     @OA\Property(property="serial_number", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Serial numbers registered")
     * )
     */
    public function bulkStore(BulkRegisterSerialNumbersRequest $request): JsonResponse
    {
        $serialNumbers = $this->serialNumberService->bulkRegisterSerialNumbers(
            $request->validated()['serial_numbers']
        );

        return $this->successResponse(
            new SerialNumberCollection($serialNumbers),
            sprintf('%d serial numbers registered successfully', $serialNumbers->count()),
            201
        );
    }

    /**
     * Display the specified serial number
     *
     * @OA\Get(
     *     path="/api/v1/inventory/serial-numbers/{id}",
     *     summary="Get serial number details",
     *     tags={"Serial Numbers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $serialNumber = $this->serialNumberService->findById($id);

        if (!$serialNumber) {
            return $this->errorResponse('Serial number not found', 404);
        }

        return $this->successResponse(
            new SerialNumberResource($serialNumber),
            'Serial number retrieved successfully'
        );
    }

    /**
     * Allocate serial number for sale
     *
     * @OA\Post(
     *     path="/api/v1/inventory/serial-numbers/{id}/allocate",
     *     summary="Allocate serial number for sale",
     *     tags={"Serial Numbers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(property="sale_order_id", type="string", format="uuid"),
     *             @OA\Property(property="warranty_months", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Serial number allocated"),
     *     @OA\Response(response=400, description="Invalid status")
     * )
     */
    public function allocate(AllocateSerialNumberRequest $request, string $id): JsonResponse
    {
        try {
            $serialNumber = $this->serialNumberService->findById($id);

            if (!$serialNumber) {
                return $this->errorResponse('Serial number not found', 404);
            }

            $warrantyMonths = $request->input('warranty_months', 12);
            $saleData = [
                'customer_id' => $request->input('customer_id'),
                'sale_order_id' => $request->input('sale_order_id'),
                'sale_date' => now(),
                'warranty_start_date' => now(),
                'warranty_end_date' => now()->addMonths($warrantyMonths),
            ];

            $serialNumber = $this->serialNumberService->allocateForSale(
                $serialNumber,
                $saleData
            );

            return $this->successResponse(
                new SerialNumberResource($serialNumber),
                'Serial number allocated successfully'
            );
        } catch (\Modules\Inventory\Exceptions\InvalidSerialNumberException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Return serial number to stock
     *
     * @OA\Post(
     *     path="/api/v1/inventory/serial-numbers/{id}/return",
     *     summary="Return serial number to stock",
     *     tags={"Serial Numbers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Serial number returned")
     * )
     */
    public function return(Request $request, string $id): JsonResponse
    {
        $serialNumber = $this->serialNumberService->findById($id);

        if (!$serialNumber) {
            return $this->errorResponse('Serial number not found', 404);
        }

        $serialNumber = $this->serialNumberService->returnToStock(
            $serialNumber,
            $request->all()
        );

        return $this->successResponse(
            new SerialNumberResource($serialNumber),
            'Serial number returned to stock'
        );
    }

    /**
     * Get available serial numbers for a product
     *
     * @OA\Get(
     *     path="/api/v1/inventory/products/{productId}/serial-numbers/available",
     *     summary="Get available serial numbers",
     *     tags={"Serial Numbers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function available(Request $request, string $productId): JsonResponse
    {
        $variantId = $request->input('variant_id');
        $warehouseId = $request->input('warehouse_id');
        
        $serialNumbers = $this->serialNumberService->getAvailableSerialNumbers(
            $productId,
            $variantId,
            $warehouseId
        );

        return $this->successResponse(
            new SerialNumberCollection($serialNumbers),
            'Available serial numbers retrieved successfully'
        );
    }

    /**
     * Get warranty information
     *
     * @OA\Get(
     *     path="/api/v1/inventory/serial-numbers/{serialNumber}/warranty",
     *     summary="Get warranty information",
     *     tags={"Serial Numbers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="serialNumber",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function warranty(string $serialNumber): JsonResponse
    {
        $warrantyInfo = $this->serialNumberService->getWarrantyInfo($serialNumber);

        if (!$warrantyInfo) {
            return $this->errorResponse('Warranty information not found', 404);
        }

        return $this->successResponse(
            $warrantyInfo,
            'Warranty information retrieved successfully'
        );
    }
}
