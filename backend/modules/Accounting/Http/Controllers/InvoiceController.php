<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Resources\InvoiceResource;
use Modules\Accounting\Services\InvoiceService;
use Modules\Core\Http\Controllers\BaseController;

/**
 * Invoice Controller
 *
 * Manages customer invoices for accounts receivable.
 * Supports invoice generation from sales orders, payment tracking, and status management.
 */
class InvoiceController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/accounting/invoices",
     *     summary="List all invoices",
     *     description="Retrieve paginated list of invoices with filtering by status, customer, date range, overdue status, and search capabilities",
     *     operationId="invoicesIndex",
     *     tags={"Accounting-Invoices"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by invoice status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft", "sent", "paid", "overdue", "cancelled", "partial"}, example="sent")
     *     ),
     *     @OA\Parameter(
     *         name="customer_id",
     *         in="query",
     *         description="Filter by customer ID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440060")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter invoices from this date (inclusive)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter invoices to this date (inclusive)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="overdue",
     *         in="query",
     *         description="Filter to show only overdue invoices",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in invoice number, customer name, or notes",
     *         required=false,
     *         @OA\Schema(type="string", example="INV-2024")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Invoice")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://api.example.com/api/accounting/invoices?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://api.example.com/api/accounting/invoices?page=10"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", example="http://api.example.com/api/accounting/invoices?page=2")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="to", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=150)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->input('status'),
                'customer_id' => $request->input('customer_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'overdue' => $request->boolean('overdue'),
                'search' => $request->input('search'),
                'per_page' => $request->integer('per_page', 15),
            ];

            $invoices = $this->invoiceService->getAll($filters);

            return $this->success(InvoiceResource::collection($invoices));
        } catch (\Exception $e) {
            return $this->error('Failed to fetch invoices: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/invoices",
     *     summary="Create a new invoice",
     *     description="Create a new customer invoice with line items. Invoice will be created in draft status by default. Optionally link to a sales order.",
     *     operationId="invoicesStore",
     *     tags={"Accounting-Invoices"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Invoice data with line items",
     *         @OA\JsonContent(ref="#/components/schemas/StoreInvoiceRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid data",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid customer, product, or date",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sales_order_id' => 'nullable|uuid|exists:sales_orders,id',
                'customer_id' => 'required|uuid|exists:customers,id',
                'invoice_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:invoice_date',
                'billing_address' => 'nullable|string',
                'currency_code' => 'nullable|string|size:3',
                'notes' => 'nullable|string',
                'terms' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'nullable|uuid|exists:products,id',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
                'items.*.discount_amount' => 'nullable|numeric|min:0',
            ]);

            $invoice = $this->invoiceService->create($validated);

            return $this->created(InvoiceResource::make($invoice), 'Invoice created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create invoice: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/invoices/generate-from-order/{orderId}",
     *     summary="Generate invoice from sales order",
     *     description="Automatically generate an invoice from an existing sales order. All order details including items, customer, and pricing will be copied to the new invoice.",
     *     operationId="invoicesGenerateFromOrder",
     *     tags={"Accounting-Invoices"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="Sales order ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440050")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice generated from sales order successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice generated from sales order successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Order already invoiced or invalid status",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sales order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function generateFromOrder(string $orderId): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->generateFromSalesOrder($orderId);

            return $this->created(InvoiceResource::make($invoice), 'Invoice generated from sales order successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Sales order not found');
        } catch (\Exception $e) {
            return $this->error('Failed to generate invoice: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/accounting/invoices/{id}",
     *     summary="Get invoice details",
     *     description="Retrieve detailed information for a specific invoice including all line items, payment history, and financial calculations",
     *     operationId="invoicesShow",
     *     tags={"Accounting-Invoices"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->getById($id);

            return $this->success($invoice);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Invoice not found');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch invoice: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/accounting/invoices/{id}",
     *     summary="Update an invoice",
     *     description="Update an existing invoice. Only draft invoices can have items modified. Status can be updated on sent invoices.",
     *     operationId="invoicesUpdate",
     *     tags={"Accounting-Invoices"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Invoice data to update",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateInvoiceRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Cannot update paid or cancelled invoice",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'sometimes|uuid|exists:customers,id',
                'status' => 'sometimes|string|in:draft,sent,paid,overdue,cancelled,partial',
                'invoice_date' => 'sometimes|date',
                'due_date' => 'sometimes|date',
                'billing_address' => 'nullable|string',
                'currency_code' => 'nullable|string|size:3',
                'notes' => 'nullable|string',
                'terms' => 'nullable|string',
                'items' => 'sometimes|array|min:1',
                'items.*.product_id' => 'nullable|uuid|exists:products,id',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
                'items.*.discount_amount' => 'nullable|numeric|min:0',
            ]);

            $invoice = $this->invoiceService->update($id, $validated);

            return $this->updated($invoice, 'Invoice updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Invoice not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update invoice: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/invoices/{id}/send",
     *     summary="Send invoice to customer",
     *     description="Send the invoice to the customer via email and update status to 'sent'. Invoice must be in draft status.",
     *     operationId="invoicesSend",
     *     tags={"Accounting-Invoices"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice sent successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invoice already sent or invalid status",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error - Failed to send email",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function send(string $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->sendInvoice($id);

            return $this->updated($invoice, 'Invoice sent successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Invoice not found');
        } catch (\Exception $e) {
            return $this->error('Failed to send invoice: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/invoices/{id}/mark-as-paid",
     *     summary="Mark invoice as paid",
     *     description="Manually mark an invoice as fully paid. This updates the invoice status to 'paid' and records the full payment date. Use payment allocation for partial payments.",
     *     operationId="invoicesMarkAsPaid",
     *     tags={"Accounting-Invoices"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice marked as paid successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice marked as paid successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invoice already paid or cancelled",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function markAsPaid(string $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->markAsPaid($id);

            return $this->updated($invoice, 'Invoice marked as paid successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Invoice not found');
        } catch (\Exception $e) {
            return $this->error('Failed to mark invoice as paid: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/accounting/invoices/{id}",
     *     summary="Delete an invoice",
     *     description="Delete an invoice. Only draft invoices can be deleted. Sent or paid invoices should be cancelled instead to maintain audit trail.",
     *     operationId="invoicesDestroy",
     *     tags={"Accounting-Invoices"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Invoice deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Cannot delete non-draft invoice",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->invoiceService->delete($id);

            return $this->deleted('Invoice deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Invoice not found');
        } catch (\Exception $e) {
            return $this->error('Failed to delete invoice: '.$e->getMessage(), 500);
        }
    }
}
