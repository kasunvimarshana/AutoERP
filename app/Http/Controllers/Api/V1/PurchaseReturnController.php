<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PurchaseReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(
        private readonly PurchaseReturnService $purchaseReturnService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['purchase_id', 'supplier_id', 'date_from', 'date_to']);

        return response()->json($this->purchaseReturnService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('purchases.manage'), 403);

        $data = $request->validate([
            'business_location_id' => ['required', 'uuid', 'exists:business_locations,id'],
            'purchase_id' => ['required', 'uuid', 'exists:purchases,id'],
            'reference_no' => ['sometimes', 'nullable', 'string', 'max:80'],
            'supplier_id' => ['sometimes', 'nullable', 'uuid', 'exists:contacts,id'],
            'return_date' => ['required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_line_id' => ['required', 'uuid', 'exists:purchase_lines,id'],
            'lines.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'lines.*.product_variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;
        $data['created_by'] = $request->user()->id;

        return response()->json($this->purchaseReturnService->create($data), 201);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('purchases.manage'), 403);

        return response()->json($this->purchaseReturnService->cancel($id));
    }
}
