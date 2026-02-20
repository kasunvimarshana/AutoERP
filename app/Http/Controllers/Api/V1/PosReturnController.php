<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PosReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosReturnController extends Controller
{
    public function __construct(
        private readonly PosReturnService $posReturnService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['pos_transaction_id', 'business_location_id']);

        return response()->json($this->posReturnService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);

        $data = $request->validate([
            'pos_transaction_id' => ['required', 'uuid', 'exists:pos_transactions,id'],
            'business_location_id' => ['sometimes', 'nullable', 'uuid', 'exists:business_locations,id'],
            'cash_register_id' => ['sometimes', 'nullable', 'uuid', 'exists:cash_registers,id'],
            'warehouse_id' => ['sometimes', 'nullable', 'uuid', 'exists:warehouses,id'],
            'reference_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'refund_method' => ['sometimes', 'in:cash,store_credit,original_payment'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.pos_transaction_line_id' => ['required', 'uuid', 'exists:pos_transaction_lines,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.restock' => ['sometimes', 'boolean'],
        ]);

        $posReturn = $this->posReturnService->create(
            $data,
            $request->user()->tenant_id,
            $request->user()->id
        );

        return response()->json($posReturn, 201);
    }
}
