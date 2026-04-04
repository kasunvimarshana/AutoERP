<?php

namespace Modules\SalesOrder\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SalesOrder\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPickingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\DTOs\SalesOrderData;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Http\Resources\SalesOrderResource;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
        private readonly CreateSalesOrderServiceInterface $createService,
        private readonly ConfirmSalesOrderServiceInterface $confirmService,
        private readonly CancelSalesOrderServiceInterface $cancelService,
        private readonly StartPickingSalesOrderServiceInterface $startPickingService,
        private readonly StartPackingSalesOrderServiceInterface $startPackingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $sos = $this->repository->findAll(
            $tenantId,
            $request->only(['status', 'customer_id', 'warehouse_id'])
        );
        return response()->json($sos);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'              => 'required|integer',
            'warehouse_id'           => 'required|integer',
            'customer_id'            => 'required|integer',
            'so_number'              => 'required|string|max:100',
            'lines'                  => 'required|array|min:1',
            'lines.*.product_id'     => 'required|integer',
            'lines.*.ordered_qty'    => 'required|numeric|min:0.0001',
            'lines.*.unit_price'     => 'required|numeric|min:0',
            'currency'               => 'nullable|string|size:3',
            'notes'                  => 'nullable|string',
            'shipping_address'       => 'nullable|string',
            'expected_delivery_date' => 'nullable|date',
        ]);

        try {
            $dto = new SalesOrderData(
                tenantId: $validated['tenant_id'],
                warehouseId: $validated['warehouse_id'],
                customerId: $validated['customer_id'],
                soNumber: $validated['so_number'],
                lines: $validated['lines'],
                currency: $validated['currency'] ?? 'USD',
                notes: $validated['notes'] ?? null,
                shippingAddress: $validated['shipping_address'] ?? null,
                expectedDeliveryDate: $validated['expected_delivery_date'] ?? null,
            );
            $so = $this->createService->execute($dto);
            return response()->json(new SalesOrderResource($so), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $so = $this->repository->findById($id);
        if (!$so) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new SalesOrderResource($so));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $so = $this->repository->findById($id);
        if (!$so) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'notes'                  => 'nullable|string',
            'shipping_address'       => 'nullable|string',
            'expected_delivery_date' => 'nullable|date',
            'currency'               => 'nullable|string|size:3',
        ]);

        $updated = $this->repository->update($so, $validated);
        return response()->json(new SalesOrderResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $so = $this->repository->findById($id);
        if (!$so) return response()->json(['message' => 'Not found'], 404);
        SalesOrderModel::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function confirm(int $id): JsonResponse
    {
        $so = $this->repository->findById($id);
        if (!$so) return response()->json(['message' => 'Not found'], 404);
        try {
            $so = $this->confirmService->execute($so);
            return response()->json(new SalesOrderResource($so));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        $so = $this->repository->findById($id);
        if (!$so) return response()->json(['message' => 'Not found'], 404);
        try {
            $so = $this->cancelService->execute($so);
            return response()->json(new SalesOrderResource($so));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function startPicking(Request $request, int $id): JsonResponse
    {
        $so = $this->repository->findById($id);
        if (!$so) return response()->json(['message' => 'Not found'], 404);
        $request->validate(['picked_by' => 'required|integer']);
        try {
            $so = $this->startPickingService->execute($so, (int) $request->input('picked_by'));
            return response()->json(new SalesOrderResource($so));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function startPacking(Request $request, int $id): JsonResponse
    {
        $so = $this->repository->findById($id);
        if (!$so) return response()->json(['message' => 'Not found'], 404);
        $request->validate(['packed_by' => 'required|integer']);
        try {
            $so = $this->startPackingService->execute($so, (int) $request->input('packed_by'));
            return response()->json(new SalesOrderResource($so));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
