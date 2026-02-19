<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['status', 'organization_id']);

        return response()->json($this->orderService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create'), 403);

        $data = $request->validate([
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'type' => ['sometimes', 'string', 'in:sale,purchase,return'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'shipping_address' => ['sometimes', 'array'],
            'billing_address' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'array'],
            'lines' => ['sometimes', 'array'],
            'lines.*.product_id' => ['sometimes', 'nullable', 'uuid', 'exists:products,id'],
            'lines.*.product_name' => ['required_with:lines', 'string', 'max:255'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'min:0.000001'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;
        $data['user_id'] = $request->user()->id;

        return response()->json($this->orderService->create($data), 201);
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('orders.confirm'), 403);

        return response()->json($this->orderService->confirm($id));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('orders.cancel'), 403);

        return response()->json($this->orderService->cancel($id));
    }
}
