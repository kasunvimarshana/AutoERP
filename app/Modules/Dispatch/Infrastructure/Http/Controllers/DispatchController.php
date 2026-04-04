<?php

namespace Modules\Dispatch\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Dispatch\Application\Contracts\CreateDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\DispatchShipmentServiceInterface;
use Modules\Dispatch\Application\Contracts\MarkDeliveredServiceInterface;
use Modules\Dispatch\Application\Contracts\ProcessDispatchServiceInterface;
use Modules\Dispatch\Application\DTOs\DispatchData;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Infrastructure\Http\Resources\DispatchResource;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchModel;

class DispatchController extends Controller
{
    public function __construct(
        private readonly DispatchRepositoryInterface $repository,
        private readonly CreateDispatchServiceInterface $createService,
        private readonly ProcessDispatchServiceInterface $processService,
        private readonly DispatchShipmentServiceInterface $dispatchShipmentService,
        private readonly MarkDeliveredServiceInterface $markDeliveredService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $dispatches = $this->repository->findAll(
            $tenantId,
            $request->only(['status', 'sales_order_id', 'warehouse_id'])
        );
        return response()->json($dispatches);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'                        => 'required|integer',
            'sales_order_id'                   => 'required|integer',
            'warehouse_id'                     => 'required|integer',
            'dispatch_number'                  => 'required|string|max:100',
            'lines'                            => 'required|array|min:1',
            'lines.*.sales_order_line_id'      => 'required|integer',
            'lines.*.product_id'               => 'required|integer',
            'lines.*.dispatched_qty'           => 'required|numeric|min:0.0001',
            'lines.*.location_id'              => 'required|integer',
            'carrier'                          => 'nullable|string|max:100',
            'tracking_number'                  => 'nullable|string|max:100',
            'shipping_address'                 => 'nullable|string',
        ]);

        try {
            $dto = new DispatchData(
                tenantId: $validated['tenant_id'],
                salesOrderId: $validated['sales_order_id'],
                warehouseId: $validated['warehouse_id'],
                dispatchNumber: $validated['dispatch_number'],
                lines: $validated['lines'],
                carrier: $validated['carrier'] ?? null,
                trackingNumber: $validated['tracking_number'] ?? null,
                shippingAddress: $validated['shipping_address'] ?? null,
            );
            $dispatch = $this->createService->execute($dto);
            return response()->json(new DispatchResource($dispatch), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $dispatch = $this->repository->findById($id);
        if (!$dispatch) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new DispatchResource($dispatch));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $dispatch = $this->repository->findById($id);
        if (!$dispatch) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'tracking_number'  => 'nullable|string|max:100',
            'carrier'          => 'nullable|string|max:100',
            'shipping_address' => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $updated = $this->repository->update($dispatch, $validated);
        return response()->json(new DispatchResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $dispatch = $this->repository->findById($id);
        if (!$dispatch) return response()->json(['message' => 'Not found'], 404);
        DispatchModel::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function process(int $id): JsonResponse
    {
        $dispatch = $this->repository->findById($id);
        if (!$dispatch) return response()->json(['message' => 'Not found'], 404);
        try {
            $dispatch = $this->processService->execute($dispatch);
            return response()->json(new DispatchResource($dispatch));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function dispatch(Request $request, int $id): JsonResponse
    {
        $dispatch = $this->repository->findById($id);
        if (!$dispatch) return response()->json(['message' => 'Not found'], 404);
        $request->validate(['dispatched_by' => 'required|integer']);
        try {
            $dispatch = $this->dispatchShipmentService->execute(
                $dispatch,
                (int) $request->input('dispatched_by')
            );
            return response()->json(new DispatchResource($dispatch));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function deliver(int $id): JsonResponse
    {
        $dispatch = $this->repository->findById($id);
        if (!$dispatch) return response()->json(['message' => 'Not found'], 404);
        try {
            $dispatch = $this->markDeliveredService->execute($dispatch);
            return response()->json(new DispatchResource($dispatch));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
