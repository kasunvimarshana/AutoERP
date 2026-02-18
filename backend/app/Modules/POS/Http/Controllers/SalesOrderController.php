<?php

namespace App\Modules\POS\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\POS\DTOs\SalesOrderDTO;
use App\Modules\POS\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesOrderController extends BaseController
{
    public function __construct(private readonly SalesOrderService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $salesOrders = $this->service->paginate($perPage, ['items.product', 'customer']);

        return $this->success($salesOrders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'user_id' => 'required|exists:users,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'notes' => 'nullable|string',
            'customer_notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
        ]);

        $dto = SalesOrderDTO::fromRequest($validated);
        $salesOrder = $this->service->create($dto);

        return $this->success($salesOrder, 'Sales order created successfully', 201);
    }

    public function show(int $id): JsonResponse
    {
        $salesOrder = $this->service->findOrFail($id, ['items.product', 'customer']);

        return $this->success($salesOrder);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'user_id' => 'required|exists:users,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'notes' => 'nullable|string',
            'customer_notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
        ]);

        $dto = SalesOrderDTO::fromRequest($validated);
        $salesOrder = $this->service->update($id, $dto);

        return $this->success($salesOrder, 'Sales order updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->success(null, 'Sales order deleted successfully');
    }

    public function confirm(int $id): JsonResponse
    {
        $salesOrder = $this->service->confirm($id);

        return $this->success($salesOrder, 'Sales order confirmed successfully');
    }

    public function cancel(int $id): JsonResponse
    {
        $salesOrder = $this->service->cancel($id);

        return $this->success($salesOrder, 'Sales order cancelled successfully');
    }

    public function search(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $salesOrders = $this->service->search($search);

        return $this->success($salesOrders);
    }

    public function byCustomer(Request $request, int $customerId): JsonResponse
    {
        $filters = $request->only(['status', 'from_date', 'to_date']);
        $salesOrders = $this->service->findByCustomer($customerId, $filters);

        return $this->success($salesOrders);
    }

    public function byStatus(string $status): JsonResponse
    {
        $salesOrders = $this->service->findByStatus($status);

        return $this->success($salesOrders);
    }
}
