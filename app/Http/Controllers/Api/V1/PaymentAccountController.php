<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentAccountController extends Controller
{
    public function __construct(
        private readonly PaymentAccountService $paymentAccountService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['is_active', 'type', 'business_location_id']);

        return response()->json($this->paymentAccountService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:cash,bank,card,mobile_money,credit,other'],
            'business_location_id' => ['sometimes', 'nullable', 'uuid', 'exists:business_locations,id'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'opening_balance' => ['sometimes', 'numeric'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'array'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->paymentAccountService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'in:cash,bank,card,mobile_money,credit,other'],
            'business_location_id' => ['sometimes', 'nullable', 'uuid', 'exists:business_locations,id'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'opening_balance' => ['sometimes', 'numeric'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'array'],
        ]);

        return response()->json($this->paymentAccountService->update($id, $data));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $this->paymentAccountService->delete($id);

        return response()->json(null, 204);
    }
}
