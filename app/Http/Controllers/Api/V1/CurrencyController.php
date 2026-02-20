<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyService $currencyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $activeOnly = (bool) $request->query('active_only', false);

        return response()->json($this->currencyService->all($tenantId, $activeOnly));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'size:3'],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->currencyService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'code' => ['sometimes', 'string', 'size:3'],
            'name' => ['sometimes', 'string', 'max:100'],
            'symbol' => ['sometimes', 'string', 'max:10'],
            'exchange_rate' => ['sometimes', 'numeric', 'min:0.000001'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json($this->currencyService->update($id, $data));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $this->currencyService->delete($id);

        return response()->json(null, 204);
    }

    public function convert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric'],
            'from' => ['required', 'string', 'size:3'],
            'to' => ['required', 'string', 'size:3'],
        ]);

        $converted = $this->currencyService->convert(
            (string) $data['amount'],
            $data['from'],
            $data['to'],
            $request->user()->tenant_id
        );

        return response()->json(['converted' => $converted, 'from' => $data['from'], 'to' => $data['to']]);
    }
}
