<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CustomerGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function __construct(
        private readonly CustomerGroupService $customerGroupService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['is_active']);

        return response()->json($this->customerGroupService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'pricing_overrides' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->customerGroupService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'pricing_overrides' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json($this->customerGroupService->update($id, $data));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $this->customerGroupService->delete($id);

        return response()->json(null, 204);
    }
}
