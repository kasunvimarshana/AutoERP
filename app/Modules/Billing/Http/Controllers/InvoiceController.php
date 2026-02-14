<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Invoices",
 *     description="Invoice and billing management endpoints"
 * )
 */
class InvoiceController extends Controller
{
    protected InvoiceService $service;

    public function __construct(InvoiceService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices",
     *     tags={"Invoices"},
     *     summary="List all invoices",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"draft", "sent", "paid", "overdue", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="customer_id",
     *         in="query",
     *         description="Filter by customer",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        $filters = $request->only(['status', 'customer_id']);
        
        $invoices = $this->service->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices",
     *     tags={"Invoices"},
     *     summary="Create a new invoice",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_id", "issue_date", "due_date", "items"},
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="issue_date", type="string", format="date"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"product_id", "quantity", "unit_price"},
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="quantity", type="number"),
     *                     @OA\Property(property="unit_price", type="number"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="tax_rate", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'subtotal' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,sent,paid,overdue,cancelled,issued,partial',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);
        
        // Set default currency if not provided
        $validated['currency'] = $validated['currency'] ?? 'USD';

        $invoice = $this->service->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully',
            'data' => $invoice
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Get invoice by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $invoice = $this->service->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice->load(['customer', 'items.product', 'payments'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Update invoice",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="status", type="string", enum={"draft", "sent", "paid", "cancelled"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled,issued,partial',
            'subtotal' => 'sometimes|numeric|min:0',
            'tax_amount' => 'sometimes|numeric|min:0',
            'discount_amount' => 'sometimes|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'paid_amount' => 'sometimes|numeric|min:0',
        ]);

        $invoice = $this->service->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'data' => $invoice
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Delete invoice",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices/{id}/send",
     *     tags={"Invoices"},
     *     summary="Send invoice to customer",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice sent successfully"
     *     )
     * )
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|email',
            'message' => 'nullable|string',
        ]);

        $this->service->sendInvoice($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice sent successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices/{id}/record-payment",
     *     tags={"Invoices"},
     *     summary="Record payment for invoice",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "payment_date", "payment_method"},
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="payment_method", type="string", enum={"cash", "bank_transfer", "credit_card", "check", "other"}),
     *             @OA\Property(property="reference", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment recorded successfully"
     *     )
     * )
     */
    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,check,other',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $payment = $this->service->recordPayment($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => $payment
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{id}/pdf",
     *     tags={"Invoices"},
     *     summary="Download invoice as PDF",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF file",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     )
     * )
     */
    public function downloadPdf(int $id)
    {
        return $this->service->generatePdf($id);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/search",
     *     tags={"Invoices"},
     *     summary="Search invoices",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');
        
        $invoices = $this->service->search($query);

        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }
}
