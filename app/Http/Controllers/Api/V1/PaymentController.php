<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['invoice_id', 'status']);

        return response()->json($this->paymentService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('payments.create'), 403);

        $data = $request->validate([
            'invoice_id' => ['sometimes', 'nullable', 'uuid', 'exists:invoices,id'],
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'method' => ['required', 'string', 'in:cash,bank,card,digital'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee_amount' => ['sometimes', 'numeric', 'min:0'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'paid_at' => ['sometimes', 'date'],
            'metadata' => ['sometimes', 'array'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->paymentService->record($data), 201);
    }
}
