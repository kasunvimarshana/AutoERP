<?php

namespace App\Modules\POS\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\POS\DTOs\QuotationDTO;
use App\Modules\POS\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends BaseController
{
    public function __construct(private readonly QuotationService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $quotations = $this->service->paginate($perPage, ['items.product', 'customer']);

        return $this->success($quotations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
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

        $dto = QuotationDTO::fromRequest($validated);
        $quotation = $this->service->create($dto);

        return $this->success($quotation, 'Quotation created successfully', 201);
    }

    public function show(int $id): JsonResponse
    {
        $quotation = $this->service->findOrFail($id, ['items.product', 'customer']);

        return $this->success($quotation);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
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

        $dto = QuotationDTO::fromRequest($validated);
        $quotation = $this->service->update($id, $dto);

        return $this->success($quotation, 'Quotation updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->success(null, 'Quotation deleted successfully');
    }

    public function convertToSalesOrder(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $salesOrder = $this->service->convertToSalesOrder($id, $validated['warehouse_id']);

        return $this->success($salesOrder, 'Quotation converted to sales order successfully', 201);
    }

    public function byCustomer(Request $request, int $customerId): JsonResponse
    {
        $filters = $request->only(['status', 'valid_only']);
        $quotations = $this->service->findByCustomer($customerId, $filters);

        return $this->success($quotations);
    }

    public function expired(): JsonResponse
    {
        $quotations = $this->service->findExpiredQuotations();

        return $this->success($quotations);
    }
}
