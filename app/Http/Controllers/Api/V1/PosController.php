<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        private readonly PosService $posService
    ) {}

    // Cash Register endpoints

    public function indexRegisters(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['business_location_id', 'status']);

        return response()->json($this->posService->paginateRegisters($tenantId, $filters, $perPage));
    }

    public function openRegister(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);

        $data = $request->validate([
            'cash_register_id' => ['required', 'uuid', 'exists:cash_registers,id'],
            'opening_amount' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $register = $this->posService->openRegister($data, $request->user()->id);

        return response()->json($register);
    }

    public function closeRegister(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);

        $data = $request->validate([
            'cash_register_id' => ['required', 'uuid', 'exists:cash_registers,id'],
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $register = $this->posService->closeRegister($data, $request->user()->id);

        return response()->json($register);
    }

    public function cashInOut(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);

        $data = $request->validate([
            'cash_register_id' => ['required', 'uuid', 'exists:cash_registers,id'],
            'type' => ['required', 'in:pay_in,pay_out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $transaction = $this->posService->cashInOut($data, $request->user()->id);

        return response()->json($transaction, 201);
    }

    // POS Transaction endpoints

    public function indexTransactions(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['business_location_id', 'status', 'date_from', 'date_to']);

        return response()->json($this->posService->paginateTransactions($tenantId, $filters, $perPage));
    }

    public function storeTransaction(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.sell'), 403);

        $data = $request->validate([
            'business_location_id' => ['required', 'uuid', 'exists:business_locations,id'],
            'cash_register_id' => ['sometimes', 'nullable', 'uuid', 'exists:cash_registers,id'],
            'warehouse_id' => ['sometimes', 'nullable', 'uuid', 'exists:warehouses,id'],
            'reference_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:contacts,id'],
            'customer_group_id' => ['sometimes', 'nullable', 'uuid', 'exists:customer_groups,id'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'lines.*.product_variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'lines.*.modifiers' => ['sometimes', 'array'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.payment_account_id' => ['sometimes', 'nullable', 'uuid', 'exists:payment_accounts,id'],
            'payments.*.reference' => ['sometimes', 'nullable', 'string', 'max:100'],
            'payments.*.metadata' => ['sometimes', 'array'],
        ]);

        $transaction = $this->posService->createTransaction(
            $data,
            $request->user()->tenant_id,
            $request->user()->id
        );

        return response()->json($transaction, 201);
    }

    public function voidTransaction(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);

        $transaction = $this->posService->voidTransaction($id, $request->user()->tenant_id);

        return response()->json($transaction);
    }
}
