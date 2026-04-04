<?php
namespace Modules\GoodsReceipt\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\GoodsReceipt\Application\Contracts\CompleteGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\DTOs\GoodsReceiptData;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Http\Resources\GoodsReceiptResource;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private readonly GoodsReceiptRepositoryInterface $repository,
        private readonly CreateGoodsReceiptServiceInterface $createService,
        private readonly InspectGoodsReceiptServiceInterface $inspectService,
        private readonly PutAwayGoodsReceiptServiceInterface $putAwayService,
        private readonly CompleteGoodsReceiptServiceInterface $completeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $grs = $this->repository->findAll($tenantId, $request->only(['status', 'supplier_id', 'warehouse_id', 'purchase_order_id']));
        return response()->json($grs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'              => 'required|integer',
            'warehouse_id'           => 'required|integer',
            'gr_number'              => 'required|string|max:100',
            'lines'                  => 'required|array|min:1',
            'lines.*.product_id'     => 'required|integer',
            'lines.*.expected_qty'   => 'required|numeric|min:0.0001',
            'lines.*.received_qty'   => 'nullable|numeric|min:0',
            'lines.*.location_id'    => 'required|integer',
            'purchase_order_id'      => 'nullable|integer',
            'supplier_id'            => 'nullable|integer',
            'supplier_reference'     => 'nullable|string',
            'notes'                  => 'nullable|string',
            'received_by'            => 'nullable|integer',
        ]);

        try {
            $dto = new GoodsReceiptData(
                tenantId: $validated['tenant_id'],
                warehouseId: $validated['warehouse_id'],
                grNumber: $validated['gr_number'],
                lines: $validated['lines'],
                purchaseOrderId: $validated['purchase_order_id'] ?? null,
                supplierId: $validated['supplier_id'] ?? null,
                supplierReference: $validated['supplier_reference'] ?? null,
                notes: $validated['notes'] ?? null,
                receivedBy: $validated['received_by'] ?? null,
            );
            $gr = $this->createService->execute($dto);
            return response()->json(new GoodsReceiptResource($gr), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $gr = $this->repository->findById($id);
        if (!$gr) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new GoodsReceiptResource($gr));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $gr = $this->repository->findById($id);
        if (!$gr) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'notes'              => 'nullable|string',
            'supplier_reference' => 'nullable|string',
        ]);

        $updated = $this->repository->update($gr, $validated);
        return response()->json(new GoodsReceiptResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $gr = $this->repository->findById($id);
        if (!$gr) return response()->json(['message' => 'Not found'], 404);
        $model = \Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptModel::findOrFail($id);
        $model->delete();
        return response()->json(null, 204);
    }

    public function inspect(Request $request, int $id): JsonResponse
    {
        $request->validate(['inspected_by' => 'required|integer']);
        try {
            $gr = $this->inspectService->execute($id, (int) $request->input('inspected_by'));
            return response()->json(new GoodsReceiptResource($gr));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function putAway(Request $request, int $id): JsonResponse
    {
        $request->validate(['put_away_by' => 'required|integer']);
        try {
            $gr = $this->putAwayService->execute($id, (int) $request->input('put_away_by'));
            return response()->json(new GoodsReceiptResource($gr));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $gr = $this->completeService->execute($id);
            return response()->json(new GoodsReceiptResource($gr));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
