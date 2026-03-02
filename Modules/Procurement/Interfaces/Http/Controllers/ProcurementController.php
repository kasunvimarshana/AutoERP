<?php

declare(strict_types=1);

namespace Modules\Procurement\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Procurement\Application\DTOs\CreatePurchaseOrderDTO;
use Modules\Procurement\Application\Services\ProcurementService;

/**
 * Procurement controller.
 *
 * Input validation and response formatting ONLY.
 * All business logic is delegated to ProcurementService.
 *
 * @OA\Tag(name="Procurement", description="Procurement and purchase order management endpoints")
 */
class ProcurementController extends Controller
{
    public function __construct(private readonly ProcurementService $service) {}

    /**
     * @OA\Post(
     *     path="/api/v1/procurement/orders",
     *     tags={"Procurement"},
     *     summary="Create a new purchase order",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vendor_id","order_date","currency_code","lines"},
     *             @OA\Property(property="vendor_id", type="integer"),
     *             @OA\Property(property="order_date", type="string", format="date"),
     *             @OA\Property(property="expected_delivery_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="currency_code", type="string", example="USD"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(
     *                 property="lines",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"product_id","uom_id","quantity","unit_cost"},
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="uom_id", type="integer"),
     *                     @OA\Property(property="quantity", type="string", example="100.0000"),
     *                     @OA\Property(property="unit_cost", type="string", example="5.5000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Purchase order created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createPurchaseOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id'                  => ['required', 'integer'],
            'order_date'                 => ['required', 'date'],
            'expected_delivery_date'     => ['nullable', 'date'],
            'currency_code'              => ['required', 'string', 'max:10'],
            'notes'                      => ['nullable', 'string'],
            'lines'                      => ['required', 'array', 'min:1'],
            'lines.*.product_id'         => ['required', 'integer'],
            'lines.*.uom_id'             => ['required', 'integer'],
            'lines.*.quantity'           => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost'          => ['required', 'numeric', 'min:0'],
        ]);

        $dto   = CreatePurchaseOrderDTO::fromArray($validated);
        $order = $this->service->createPurchaseOrder($dto);

        return ApiResponse::created($order->load('lines'), 'Purchase order created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/procurement/orders",
     *     tags={"Procurement"},
     *     summary="List purchase orders",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="vendor_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of purchase orders"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listOrders(Request $request): JsonResponse
    {
        $orders = $this->service->listOrders($request->query());

        return ApiResponse::success($orders, 'Purchase orders retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/procurement/orders/{id}/receive",
     *     tags={"Procurement"},
     *     summary="Receive goods against a purchase order",
     *     description="When `warehouse_id` is provided per line, inventory is updated automatically (purchase_receipt transaction).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lines"},
     *             @OA\Property(
     *                 property="lines",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"purchase_order_line_id","quantity_received","unit_cost"},
     *                     @OA\Property(property="purchase_order_line_id", type="integer"),
     *                     @OA\Property(property="quantity_received", type="string", example="100.0000"),
     *                     @OA\Property(property="unit_cost", type="string", example="5.5000"),
     *                     @OA\Property(property="warehouse_id", type="integer", nullable=true, description="When provided, triggers automatic inventory update (purchase_receipt)")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Goods receipt created"),
     *     @OA\Response(response=404, description="Purchase order not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function receiveGoods(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'lines'                              => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id'     => ['required', 'integer'],
            'lines.*.quantity_received'          => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost'                  => ['required', 'numeric', 'min:0'],
            'lines.*.warehouse_id'               => ['sometimes', 'nullable', 'integer'],
        ]);

        $receipt = $this->service->receiveGoods($id, $validated['lines']);

        return ApiResponse::created($receipt->load('lines'), 'Goods received.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/procurement/orders/{id}",
     *     tags={"Procurement"},
     *     summary="Get a single purchase order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Purchase order data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showPurchaseOrder(int $id): JsonResponse
    {
        $order = $this->service->showPurchaseOrder($id);

        return ApiResponse::success($order, 'Purchase order retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/procurement/orders/{id}",
     *     tags={"Procurement"},
     *     summary="Update a purchase order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="expected_delivery_date", type="string", format="date", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Purchase order updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updatePurchaseOrder(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status'                  => ['sometimes', 'required', 'string', 'max:50'],
            'notes'                   => ['nullable', 'string'],
            'expected_delivery_date'  => ['nullable', 'date'],
        ]);

        $order = $this->service->updatePurchaseOrder($id, $validated);

        return ApiResponse::success($order, 'Purchase order updated.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/procurement/vendor-bills/{id}",
     *     tags={"Procurement"},
     *     summary="Get a single vendor bill",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Vendor bill data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showVendorBill(int $id): JsonResponse
    {
        $bill = $this->service->showVendorBill($id);

        return ApiResponse::success($bill, 'Vendor bill retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/procurement/vendors/{id}",
     *     tags={"Procurement"},
     *     summary="Update a vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string", nullable=true),
     *             @OA\Property(property="address", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Vendor updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateVendor(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'required', 'email', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $vendor = $this->service->updateVendor($id, $validated);

        return ApiResponse::success($vendor, 'Vendor updated.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/procurement/orders/{id}/three-way-match",
     *     tags={"Procurement"},
     *     summary="Perform a three-way match for a purchase order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Three-way match result"),
     *     @OA\Response(response=404, description="Purchase order not found")
     * )
     */
    public function threeWayMatch(int $id): JsonResponse
    {
        $result = $this->service->threeWayMatch($id);

        return ApiResponse::success($result, 'Three-way match completed.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/procurement/vendors",
     *     tags={"Procurement"},
     *     summary="List vendors",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="active_only", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="List of vendors"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listVendors(Request $request): JsonResponse
    {
        $vendors = $this->service->listVendors($request->query());

        return ApiResponse::success($vendors, 'Vendors retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/procurement/vendors",
     *     tags={"Procurement"},
     *     summary="Create a new vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", nullable=true),
     *             @OA\Property(property="phone", type="string", nullable=true),
     *             @OA\Property(property="address", type="string", nullable=true),
     *             @OA\Property(property="vendor_code", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Vendor created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createVendor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['nullable', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'address'     => ['nullable', 'string'],
            'vendor_code' => ['nullable', 'string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $dto    = \Modules\Procurement\Application\DTOs\CreateVendorDTO::fromArray($validated);
        $vendor = $this->service->createVendor($dto);

        return ApiResponse::created($vendor, 'Vendor created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/procurement/vendors/{id}",
     *     tags={"Procurement"},
     *     summary="Get a single vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Vendor data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showVendor(int $id): JsonResponse
    {
        $vendor = $this->service->showVendor($id);

        return ApiResponse::success($vendor, 'Vendor retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/procurement/vendor-bills",
     *     tags={"Procurement"},
     *     summary="List vendor bills",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="vendor_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="purchase_order_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of vendor bills"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listVendorBills(Request $request): JsonResponse
    {
        $bills = $this->service->listVendorBills($request->query());

        return ApiResponse::success($bills, 'Vendor bills retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/procurement/vendor-bills",
     *     tags={"Procurement"},
     *     summary="Create a vendor bill",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vendor_id","total_amount"},
     *             @OA\Property(property="vendor_id", type="integer"),
     *             @OA\Property(property="total_amount", type="string", example="500.00"),
     *             @OA\Property(property="purchase_order_id", type="integer", nullable=true),
     *             @OA\Property(property="due_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Vendor bill created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createVendorBill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id'         => ['required', 'integer'],
            'total_amount'      => ['required', 'numeric', 'min:0'],
            'purchase_order_id' => ['nullable', 'integer'],
            'due_date'          => ['nullable', 'date'],
            'notes'             => ['nullable', 'string'],
        ]);

        $dto  = \Modules\Procurement\Application\DTOs\CreateVendorBillDTO::fromArray($validated);
        $bill = $this->service->createVendorBill($dto);

        return ApiResponse::created($bill, 'Vendor bill created.');
    }
}
