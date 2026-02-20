<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BarcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function __construct(
        private readonly BarcodeService $barcodeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['search', 'type']);

        return response()->json($this->barcodeService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['sometimes', 'string', 'max:30'],
            'width' => ['sometimes', 'numeric', 'min:0.1'],
            'height' => ['sometimes', 'numeric', 'min:0.1'],
            'no_of_prints' => ['sometimes', 'integer', 'min:1'],
            'is_default' => ['sometimes', 'boolean'],
            'sticker_size' => ['sometimes', 'string', 'max:30'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->barcodeService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'string', 'max:30'],
            'width' => ['sometimes', 'numeric', 'min:0.1'],
            'height' => ['sometimes', 'numeric', 'min:0.1'],
            'no_of_prints' => ['sometimes', 'integer', 'min:1'],
            'is_default' => ['sometimes', 'boolean'],
            'sticker_size' => ['sometimes', 'string', 'max:30'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ]);

        return response()->json($this->barcodeService->update($id, $data));
    }

    public function setDefault(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);
        $tenantId = $request->user()->tenant_id;

        return response()->json($this->barcodeService->setDefault($id, $tenantId));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);
        $this->barcodeService->delete($id);

        return response()->json(null, 204);
    }
}
