<?php

namespace App\Modules\POS\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\POS\DTOs\InvoiceDTO;
use App\Modules\POS\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends BaseController
{
    public function __construct(private readonly InvoiceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $invoices = $this->service->paginate($perPage, ['items.product', 'customer', 'payments']);

        return $this->success($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
        ]);

        $dto = InvoiceDTO::fromRequest($validated);
        $invoice = $this->service->create($dto);

        return $this->success($invoice, 'Invoice created successfully', 201);
    }

    public function show(int $id): JsonResponse
    {
        $invoice = $this->service->findOrFail($id, ['items.product', 'customer', 'payments']);

        return $this->success($invoice);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
        ]);

        $dto = InvoiceDTO::fromRequest($validated);
        $invoice = $this->service->update($id, $dto);

        return $this->success($invoice, 'Invoice updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->success(null, 'Invoice deleted successfully');
    }

    public function createFromSalesOrder(int $salesOrderId): JsonResponse
    {
        $invoice = $this->service->createFromSalesOrder($salesOrderId);

        return $this->success($invoice, 'Invoice created from sales order successfully', 201);
    }

    public function byCustomer(Request $request, int $customerId): JsonResponse
    {
        $filters = $request->only(['status', 'from_date', 'to_date']);
        $invoices = $this->service->findByCustomer($customerId, $filters);

        return $this->success($invoices);
    }

    public function byStatus(string $status): JsonResponse
    {
        $invoices = $this->service->findByStatus($status);

        return $this->success($invoices);
    }

    public function overdue(): JsonResponse
    {
        $invoices = $this->service->findOverdueInvoices();

        return $this->success($invoices);
    }
}
