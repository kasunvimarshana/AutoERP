<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('purchases.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['status', 'payment_status', 'supplier_id']);

        return response()->json($this->purchaseService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('purchases.create'), 403);

        $data = $request->validate([
            'business_location_id' => ['sometimes', 'nullable', 'uuid', 'exists:business_locations,id'],
            'reference_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'supplier_id' => ['sometimes', 'nullable', 'uuid', 'exists:contacts,id'],
            'purchase_date' => ['required', 'date'],
            'expected_delivery_date' => ['sometimes', 'nullable', 'date'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'shipping_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'lines.*.product_variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'lines.*.quantity_ordered' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ]);

        $purchase = $this->purchaseService->create(
            $data,
            $request->user()->tenant_id,
            $request->user()->id
        );

        return response()->json($purchase, 201);
    }

    public function receive(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('purchases.receive'), 403);

        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_line_id' => ['required', 'uuid', 'exists:purchase_lines,id'],
            'lines.*.quantity_received' => ['required', 'numeric', 'min:0.001'],
            'lines.*.warehouse_id' => ['sometimes', 'nullable', 'uuid', 'exists:warehouses,id'],
        ]);

        $purchase = $this->purchaseService->receive($id, $data['lines'], $request->user()->tenant_id);

        return response()->json($purchase);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('purchases.manage'), 403);

        $purchase = $this->purchaseService->cancel($id, $request->user()->tenant_id);

        return response()->json($purchase);
    }
}
