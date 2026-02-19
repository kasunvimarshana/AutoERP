<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['status']);

        return response()->json($this->invoiceService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('invoices.create'), 403);

        $data = $request->validate([
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'order_id' => ['sometimes', 'nullable', 'uuid', 'exists:orders,id'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'billing_address' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'issue_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'items' => ['sometimes', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->invoiceService->create($data), 201);
    }

    public function send(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('invoices.send'), 403);

        return response()->json($this->invoiceService->send($id));
    }

    public function void(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('invoices.void'), 403);

        return response()->json($this->invoiceService->void($id));
    }
}
