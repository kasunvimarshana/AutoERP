<?php
namespace Modules\PurchaseOrder\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PurchaseOrder\Application\Contracts\ApprovePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\DTOs\PurchaseOrderData;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Http\Resources\PurchaseOrderResource;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
        private readonly CreatePurchaseOrderServiceInterface $createService,
        private readonly ApprovePurchaseOrderServiceInterface $approveService,
        private readonly CancelPurchaseOrderServiceInterface $cancelService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $pos = $this->repository->findAll($tenantId, $request->only(['status', 'supplier_id', 'warehouse_id']));
        return response()->json($pos);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'              => 'required|integer',
            'warehouse_id'           => 'required|integer',
            'supplier_id'            => 'required|integer',
            'po_number'              => 'required|string|max:100',
            'lines'                  => 'required|array|min:1',
            'lines.*.product_id'     => 'required|integer',
            'lines.*.ordered_qty'    => 'required|numeric|min:0.0001',
            'lines.*.unit_cost'      => 'required|numeric|min:0',
            'currency'               => 'nullable|string|size:3',
            'notes'                  => 'nullable|string',
            'expected_delivery_date' => 'nullable|date',
        ]);

        try {
            $dto = new PurchaseOrderData(
                tenantId: $validated['tenant_id'],
                warehouseId: $validated['warehouse_id'],
                supplierId: $validated['supplier_id'],
                poNumber: $validated['po_number'],
                lines: $validated['lines'],
                currency: $validated['currency'] ?? 'USD',
                notes: $validated['notes'] ?? null,
                expectedDeliveryDate: $validated['expected_delivery_date'] ?? null,
            );
            $po = $this->createService->execute($dto);
            return response()->json(new PurchaseOrderResource($po), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $po = $this->repository->findById($id);
        if (!$po) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new PurchaseOrderResource($po));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $po = $this->repository->findById($id);
        if (!$po) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'notes'                  => 'nullable|string',
            'expected_delivery_date' => 'nullable|date',
            'currency'               => 'nullable|string|size:3',
        ]);

        $updated = $this->repository->update($po, $validated);
        return response()->json(new PurchaseOrderResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $po = $this->repository->findById($id);
        if (!$po) return response()->json(['message' => 'Not found'], 404);
        $model = \Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel::findOrFail($id);
        $model->delete();
        return response()->json(null, 204);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate(['approved_by' => 'required|integer']);
        try {
            $po = $this->approveService->execute($id, (int) $request->input('approved_by'));
            return response()->json(new PurchaseOrderResource($po));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $po = $this->cancelService->execute($id);
            return response()->json(new PurchaseOrderResource($po));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
