<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Printer;
use App\Services\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    public function __construct(
        private readonly PrinterService $printerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['business_location_id', 'search']);

        return response()->json($this->printerService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'business_location_id' => ['sometimes', 'nullable', 'uuid', 'exists:business_locations,id'],
            'connection_type' => ['sometimes', 'string', 'in:network,windows,linux,browser'],
            'capability_profile' => ['sometimes', 'string', 'max:30'],
            'char_per_line' => ['sometimes', 'nullable', 'string', 'max:10'],
            'ip_address' => ['sometimes', 'nullable', 'ip'],
            'port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->printerService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'business_location_id' => ['sometimes', 'nullable', 'uuid', 'exists:business_locations,id'],
            'connection_type' => ['sometimes', 'string', 'in:network,windows,linux,browser'],
            'capability_profile' => ['sometimes', 'string', 'max:30'],
            'char_per_line' => ['sometimes', 'nullable', 'string', 'max:10'],
            'ip_address' => ['sometimes', 'nullable', 'ip'],
            'port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        return response()->json($this->printerService->update($id, $data));
    }

    public function capabilityProfiles(): JsonResponse
    {
        return response()->json(Printer::capabilityProfiles());
    }

    public function connectionTypes(): JsonResponse
    {
        return response()->json(Printer::connectionTypes());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $this->printerService->delete($id);

        return response()->json(null, 204);
    }
}
