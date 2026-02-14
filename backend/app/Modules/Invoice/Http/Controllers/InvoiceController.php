<?php

namespace App\Modules\Invoice\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Invoice\Services\InvoiceService;
use App\Modules\Invoice\DTOs\InvoiceDTO;
use App\Modules\Invoice\DTOs\InvoiceItemDTO;
use App\Modules\Invoice\Http\Requests\StoreInvoiceRequest;
use App\Modules\Invoice\Http\Requests\UpdateInvoiceRequest;
use App\Modules\Invoice\Http\Requests\StoreInvoiceItemRequest;
use App\Modules\Invoice\Http\Requests\RecordPaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Invoices",
 *     description="Invoice management endpoints"
 * )
 */
class InvoiceController extends BaseController
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
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoices retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $invoices = $this->service->paginate($perPage, ['tenant', 'branch', 'customer', 'order', 'invoiceItems']);
            
            return $this->successResponse($invoices, 'Invoices retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices",
     *     tags={"Invoices"},
     *     summary="Create a new invoice",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id", "branch_id", "customer_id", "invoice_number", "invoice_date", "due_date"},
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(property="branch_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(property="order_id", type="string", format="uuid"),
     *             @OA\Property(property="invoice_number", type="string", example="INV-2024-001"),
     *             @OA\Property(property="invoice_date", type="string", format="date"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"draft", "sent", "paid", "overdue", "cancelled"}),
     *             @OA\Property(property="subtotal", type="number", format="float"),
     *             @OA\Property(property="tax_amount", type="number", format="float"),
     *             @OA\Property(property="discount_amount", type="number", format="float"),
     *             @OA\Property(property="total_amount", type="number", format="float"),
     *             @OA\Property(property="paid_amount", type="number", format="float"),
     *             @OA\Property(property="balance_due", type="number", format="float"),
     *             @OA\Property(property="payment_status", type="string", enum={"unpaid", "partial", "paid"}),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
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
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            $dto = InvoiceDTO::fromArray($request->validated());
            $invoice = $this->service->createInvoice($dto);
            
            return $this->successResponse($invoice, 'Invoice created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Get a specific invoice",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $invoice = $this->service->findById($id, ['tenant', 'branch', 'customer', 'order', 'invoiceItems.productVariant', 'payments']);
            
            if (!$invoice) {
                return $this->errorResponse('Invoice not found', 404);
            }
            
            return $this->successResponse($invoice, 'Invoice retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Update an invoice",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="branch_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(property="order_id", type="string", format="uuid"),
     *             @OA\Property(property="invoice_number", type="string"),
     *             @OA\Property(property="invoice_date", type="string", format="date"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"draft", "sent", "paid", "overdue", "cancelled"}),
     *             @OA\Property(property="subtotal", type="number", format="float"),
     *             @OA\Property(property="tax_amount", type="number", format="float"),
     *             @OA\Property(property="discount_amount", type="number", format="float"),
     *             @OA\Property(property="total_amount", type="number", format="float"),
     *             @OA\Property(property="paid_amount", type="number", format="float"),
     *             @OA\Property(property="balance_due", type="number", format="float"),
     *             @OA\Property(property="payment_status", type="string", enum={"unpaid", "partial", "paid"}),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
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
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
    public function update(UpdateInvoiceRequest $request, string $id): JsonResponse
    {
        try {
            $dto = InvoiceDTO::fromArray($request->validated());
            $result = $this->service->updateInvoice($id, $dto);
            
            if (!$result) {
                return $this->errorResponse('Invoice not found or update failed', 404);
            }
            
            $invoice = $this->service->findById($id, ['tenant', 'branch', 'customer', 'order', 'invoiceItems', 'payments']);
            
            return $this->successResponse($invoice, 'Invoice updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Delete an invoice",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $result = $this->service->deleteInvoice($id);
            
            if (!$result) {
                return $this->errorResponse('Invoice not found or delete failed', 404);
            }
            
            return $this->successResponse(null, 'Invoice deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices/{id}/send",
     *     tags={"Invoices"},
     *     summary="Send an invoice (mark as sent)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
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
     *         response=404,
     *         description="Invoice not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invoice cannot be sent"
     *     )
     * )
     */
    public function send(string $id): JsonResponse
    {
        try {
            $result = $this->service->sendInvoice($id);
            
            if (!$result) {
                return $this->errorResponse('Invoice not found or send failed', 404);
            }
            
            $invoice = $this->service->findById($id, ['tenant', 'branch', 'customer', 'invoiceItems']);
            
            return $this->successResponse($invoice, 'Invoice sent successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{id}/pdf",
     *     tags={"Invoices"},
     *     summary="Generate and download invoice PDF",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="PDF generated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="url", type="string", example="https://example.com/storage/invoices/tenant-id/invoice-INV-2024-001.pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
    public function pdf(string $id): JsonResponse
    {
        try {
            $pdfUrl = $this->service->generatePDF($id);
            
            return $this->successResponse(['url' => $pdfUrl], 'PDF generated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{id}/items",
     *     tags={"Invoices"},
     *     summary="Get all items for an invoice",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice items retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/InvoiceItem"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
    public function items(string $id): JsonResponse
    {
        try {
            $invoice = $this->service->findById($id, ['invoiceItems.productVariant.product']);
            
            if (!$invoice) {
                return $this->errorResponse('Invoice not found', 404);
            }
            
            return $this->successResponse($invoice->invoiceItems, 'Invoice items retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices/{id}/items",
     *     tags={"Invoices"},
     *     summary="Add an item to an invoice",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"description", "quantity", "unit_price"},
     *             @OA\Property(property="product_variant_id", type="string", format="uuid"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="quantity", type="number", format="float"),
     *             @OA\Property(property="unit_price", type="number", format="float"),
     *             @OA\Property(property="tax_rate", type="number", format="float"),
     *             @OA\Property(property="discount_amount", type="number", format="float"),
     *             @OA\Property(property="line_total", type="number", format="float"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice item added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice item added successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/InvoiceItem")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
    public function addItem(StoreInvoiceItemRequest $request, string $id): JsonResponse
    {
        try {
            $dto = InvoiceItemDTO::fromArray($request->validated());
            $invoiceItem = $this->service->addInvoiceItem($id, $dto);
            
            return $this->successResponse($invoiceItem, 'Invoice item added successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices/{id}/payments",
     *     tags={"Invoices"},
     *     summary="Record a payment for an invoice",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invoice ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", example=500.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment recorded successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid payment amount"
     *     )
     * )
     */
    public function recordPayment(RecordPaymentRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->service->recordPayment($id, $request->validated()['amount']);
            
            if (!$result) {
                return $this->errorResponse('Invoice not found or payment failed', 404);
            }
            
            $invoice = $this->service->findById($id, ['tenant', 'branch', 'customer', 'invoiceItems', 'payments']);
            
            return $this->successResponse($invoice, 'Payment recorded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
