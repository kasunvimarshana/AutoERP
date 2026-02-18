<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Sales\Http\Requests\StoreQuotationRequest;
use Modules\Sales\Http\Requests\UpdateQuotationRequest;
use Modules\Sales\Services\QuotationService;

/**
 * Quotation Controller
 *
 * Handles HTTP requests for sales quotation operations including creation,
 * sending, acceptance, and conversion to sales orders.
 */
class QuotationController extends BaseController
{
    public function __construct(
        protected QuotationService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/sales/quotations",
     *     summary="List quotations",
     *     description="Retrieve paginated list of sales quotations with optional filtering",
     *     operationId="quotationsIndex",
     *     tags={"Sales-Quotations"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by quotation status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft", "sent", "accepted", "rejected", "expired", "converted"}, example="sent")
     *     ),
     *     @OA\Parameter(
     *         name="customer_id",
     *         in="query",
     *         description="Filter by customer ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter quotations from this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter quotations to this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in quote number and customer name",
     *         required=false,
     *         @OA\Schema(type="string", example="QUOT-2024")
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
     *             @OA\Property(property="message", type="string", example="Quotations retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Quotation")
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
            'status', 'customer_id', 'from_date', 'to_date', 'search',
            'sort_by', 'sort_order', 'per_page',
        ]);

        $quotations = $this->service->getAll($filters, (int) ($request->input('per_page', 15)));

        return $this->successResponse($quotations, 'Quotations retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/sales/quotations/{id}",
     *     summary="Get quotation details",
     *     description="Retrieve detailed information about a specific quotation including line items",
     *     operationId="quotationShow",
     *     tags={"Sales-Quotations"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Quotation ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotation retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Quotation")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quotation not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function show(int $id): JsonResponse
    {
        $quotation = $this->service->findById($id);

        if (! $quotation) {
            return $this->errorResponse('Quotation not found', 404);
        }

        return $this->successResponse($quotation, 'Quotation retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/sales/quotations",
     *     summary="Create quotation",
     *     description="Create a new sales quotation with line items",
     *     operationId="quotationStore",
     *     tags={"Sales-Quotations"},
     *     security={{"sanctum_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Quotation data",
     *         @OA\JsonContent(ref="#/components/schemas/QuotationRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Quotation created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotation created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Quotation")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function store(StoreQuotationRequest $request): JsonResponse
    {
        try {
            $quotation = $this->service->create($request->validated());

            return $this->successResponse($quotation, 'Quotation created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/sales/quotations/{id}",
     *     summary="Update quotation",
     *     description="Update an existing quotation",
     *     operationId="quotationUpdate",
     *     tags={"Sales-Quotations"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Quotation ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated quotation data",
     *         @OA\JsonContent(ref="#/components/schemas/QuotationRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quotation updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotation updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Quotation")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quotation not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function update(UpdateQuotationRequest $request, int $id): JsonResponse
    {
        try {
            $quotation = $this->service->update($id, $request->validated());

            return $this->successResponse($quotation, 'Quotation updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/sales/quotations/{id}",
     *     summary="Delete quotation",
     *     description="Delete a quotation (soft delete)",
     *     operationId="quotationDestroy",
     *     tags={"Sales-Quotations"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Quotation ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quotation deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotation deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quotation not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Cannot delete quotation", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->successResponse(null, 'Quotation deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sales/quotations/{id}/send",
     *     summary="Send quotation",
     *     description="Mark quotation as sent to customer",
     *     operationId="quotationSend",
     *     tags={"Sales-Quotations"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Quotation ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quotation sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotation sent successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Quotation")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quotation not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Cannot send quotation", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function send(int $id): JsonResponse
    {
        try {
            $quotation = $this->service->send($id);

            return $this->successResponse($quotation, 'Quotation sent successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sales/quotations/{id}/accept",
     *     summary="Accept quotation",
     *     description="Mark quotation as accepted by customer",
     *     operationId="quotationAccept",
     *     tags={"Sales-Quotations"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Quotation ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quotation accepted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotation accepted successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Quotation")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quotation not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Cannot accept quotation", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function accept(int $id): JsonResponse
    {
        try {
            $quotation = $this->service->accept($id);

            return $this->successResponse($quotation, 'Quotation accepted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sales/quotations/{id}/reject",
     *     summary="Reject quotation",
     *     description="Mark quotation as rejected by customer",
     *     operationId="quotationReject",
     *     tags={"Sales-Quotations"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Quotation ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quotation rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotation rejected successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Quotation")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quotation not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Cannot reject quotation", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function reject(int $id): JsonResponse
    {
        try {
            $quotation = $this->service->reject($id);

            return $this->successResponse($quotation, 'Quotation rejected successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sales/quotations/{id}/convert",
     *     summary="Convert quotation to sales order",
     *     description="Convert an accepted quotation to a sales order",
     *     operationId="quotationConvert",
     *     tags={"Sales-Quotations"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Quotation ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quotation converted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quotation converted to sales order successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/SalesOrder")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quotation not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Cannot convert quotation", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function convert(int $id): JsonResponse
    {
        try {
            $salesOrder = $this->service->convertToSalesOrder($id);

            return $this->successResponse($salesOrder, 'Quotation converted to sales order successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
