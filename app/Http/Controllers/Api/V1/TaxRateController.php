<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TaxRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function __construct(
        private readonly TaxRateService $taxRateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['is_active', 'type']);

        return response()->json($this->taxRateService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'type' => ['sometimes', 'in:simple,compound,group'],
            'is_active' => ['sometimes', 'boolean'],
            'sub_tax_ids' => ['sometimes', 'array'],
            'sub_tax_ids.*' => ['uuid', 'exists:tax_rates,id'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->taxRateService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'type' => ['sometimes', 'in:simple,compound,group'],
            'is_active' => ['sometimes', 'boolean'],
            'sub_tax_ids' => ['sometimes', 'array'],
            'sub_tax_ids.*' => ['uuid', 'exists:tax_rates,id'],
        ]);

        return response()->json($this->taxRateService->update($id, $data));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $this->taxRateService->delete($id);

        return response()->json(null, 204);
    }
}
