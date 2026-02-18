<?php

declare(strict_types=1);

namespace Modules\Purchasing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Purchasing\Http\Requests\StoreSupplierRequest;
use Modules\Purchasing\Http\Requests\UpdateSupplierRequest;
use Modules\Purchasing\Services\SupplierService;

/**
 * Supplier Controller
 *
 * Handles HTTP requests for supplier/vendor operations.
 */
class SupplierController extends BaseController
{
    public function __construct(
        protected SupplierService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/purchasing/suppliers",
     *     summary="List suppliers",
     *     description="Retrieve paginated list of suppliers/vendors with optional filtering",
     *     operationId="suppliersIndex",
     *     tags={"Purchasing-Suppliers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive", "suspended"}, example="active")
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         description="Filter by country",
     *         required=false,
     *         @OA\Schema(type="string", example="USA")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in supplier name, email, and code",
     *         required=false,
     *         @OA\Schema(type="string", example="ABC Supplies")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Suppliers retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Supplier")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'country', 'search',
            'sort_by', 'sort_order', 'per_page',
        ]);

        $suppliers = $this->service->list($filters);

        return $this->successResponse($suppliers, 'Suppliers retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/purchasing/suppliers/{id}",
     *     summary="Get supplier details",
     *     description="Retrieve detailed information about a specific supplier",
     *     operationId="supplierShow",
     *     tags={"Purchasing-Suppliers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Supplier ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Supplier retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Supplier")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Supplier not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function show(int $id): JsonResponse
    {
        $supplier = $this->service->find($id);

        if (! $supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }

        return $this->successResponse($supplier, 'Supplier retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/purchasing/suppliers",
     *     summary="Create supplier",
     *     description="Create a new supplier/vendor",
     *     operationId="supplierStore",
     *     tags={"Purchasing-Suppliers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Supplier data",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Supplier created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Supplier created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Supplier")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        try {
            $supplier = $this->service->create($request->validated());

            return $this->successResponse($supplier, 'Supplier created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/purchasing/suppliers/{id}",
     *     summary="Update supplier",
     *     description="Update an existing supplier",
     *     operationId="supplierUpdate",
     *     tags={"Purchasing-Suppliers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Supplier ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated supplier data",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Supplier updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Supplier")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Supplier not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        try {
            $supplier = $this->service->update($id, $request->validated());

            return $this->successResponse($supplier, 'Supplier updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/purchasing/suppliers/{id}",
     *     summary="Delete supplier",
     *     description="Delete a supplier",
     *     operationId="supplierDestroy",
     *     tags={"Purchasing-Suppliers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Supplier ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Supplier deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Supplier not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->successResponse(null, 'Supplier deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/purchasing/suppliers/{id}/activate",
     *     summary="Activate supplier",
     *     description="Activate a suspended or inactive supplier",
     *     operationId="supplierActivate",
     *     tags={"Purchasing-Suppliers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Supplier ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier activated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Supplier activated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Supplier")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Supplier not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $supplier = $this->service->activate($id);

            return $this->successResponse($supplier, 'Supplier activated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/purchasing/suppliers/{id}/suspend",
     *     summary="Suspend supplier",
     *     description="Suspend a supplier from doing business",
     *     operationId="supplierSuspend",
     *     tags={"Purchasing-Suppliers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Supplier ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier suspended successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Supplier suspended successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Supplier")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Supplier not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function suspend(int $id): JsonResponse
    {
        try {
            $supplier = $this->service->suspend($id);

            return $this->successResponse($supplier, 'Supplier suspended successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
