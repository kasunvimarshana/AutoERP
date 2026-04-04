<?php
namespace Modules\StockMovement\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\StockMovement\Application\Contracts\CreateStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\TransferStockServiceInterface;
use Modules\StockMovement\Application\DTOs\StockMovementData;
use Modules\StockMovement\Application\DTOs\TransferStockData;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\StockMovement\Infrastructure\Http\Resources\StockMovementResource;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $repository,
        private readonly CreateStockMovementServiceInterface $createService,
        private readonly TransferStockServiceInterface $transferService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $movements = $this->repository->findAll($tenantId, $request->only(['movement_type', 'product_id', 'warehouse_id']));
        return response()->json($movements);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'        => 'required|integer',
            'product_id'       => 'required|integer',
            'warehouse_id'     => 'required|integer',
            'location_id'      => 'required|integer',
            'movement_type'    => 'required|string',
            'quantity'         => 'required|numeric|min:0.0001',
            'reference_number' => 'required|string|max:100',
            'variant_id'       => 'nullable|integer',
            'batch_id'         => 'nullable|integer',
            'lot_number'       => 'nullable|string',
            'serial_number'    => 'nullable|string',
            'unit_cost'        => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
            'moved_by'         => 'nullable|integer',
        ]);

        try {
            $dto = new StockMovementData(
                tenantId: $validated['tenant_id'],
                productId: $validated['product_id'],
                warehouseId: $validated['warehouse_id'],
                locationId: $validated['location_id'],
                movementType: $validated['movement_type'],
                quantity: (float) $validated['quantity'],
                referenceNumber: $validated['reference_number'],
                variantId: $validated['variant_id'] ?? null,
                batchId: $validated['batch_id'] ?? null,
                lotNumber: $validated['lot_number'] ?? null,
                serialNumber: $validated['serial_number'] ?? null,
                unitCost: isset($validated['unit_cost']) ? (float) $validated['unit_cost'] : null,
                notes: $validated['notes'] ?? null,
                movedBy: $validated['moved_by'] ?? null,
            );
            $movement = $this->createService->execute($dto);
            return response()->json(new StockMovementResource($movement), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $movement = $this->repository->findById($id);
        if (!$movement) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new StockMovementResource($movement));
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'         => 'required|integer',
            'product_id'        => 'required|integer',
            'from_warehouse_id' => 'required|integer',
            'from_location_id'  => 'required|integer',
            'to_warehouse_id'   => 'required|integer',
            'to_location_id'    => 'required|integer',
            'quantity'          => 'required|numeric|min:0.0001',
            'reference'         => 'required|string|max:100',
            'batch_id'          => 'nullable|integer',
            'variant_id'        => 'nullable|integer',
        ]);

        try {
            $dto = new TransferStockData(
                tenantId: $validated['tenant_id'],
                productId: $validated['product_id'],
                fromWarehouseId: $validated['from_warehouse_id'],
                fromLocationId: $validated['from_location_id'],
                toWarehouseId: $validated['to_warehouse_id'],
                toLocationId: $validated['to_location_id'],
                quantity: (float) $validated['quantity'],
                reference: $validated['reference'],
                batchId: $validated['batch_id'] ?? null,
                variantId: $validated['variant_id'] ?? null,
            );
            [$from, $to] = $this->transferService->execute($dto);
            return response()->json([
                'from' => new StockMovementResource($from),
                'to'   => new StockMovementResource($to),
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
