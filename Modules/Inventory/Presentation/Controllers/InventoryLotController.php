<?php

namespace Modules\Inventory\Presentation\Controllers;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\UseCases\BlockLotUseCase;
use Modules\Inventory\Application\UseCases\CreateLotUseCase;
use Modules\Inventory\Domain\Contracts\InventoryLotRepositoryInterface;
use Modules\Inventory\Presentation\Requests\StoreInventoryLotRequest;

class InventoryLotController extends Controller
{
    public function __construct(
        private InventoryLotRepositoryInterface $lotRepo,
        private CreateLotUseCase $createLot,
        private BlockLotUseCase  $blockLot,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');

        $filters = array_filter([
            'product_id'    => $request->query('product_id'),
            'status'        => $request->query('status'),
            'tracking_type' => $request->query('tracking_type'),
            'expiry_before' => $request->query('expiry_before'),
        ]);

        $paginator = $this->lotRepo->paginate($tenantId, $filters, (int) $request->query('per_page', 20));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
            ],
        ]);
    }

    public function store(StoreInventoryLotRequest $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');

        try {
            $lot = $this->createLot->execute(array_merge($request->validated(), ['tenant_id' => $tenantId]));
            return response()->json(['data' => $lot], 201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $lot = $this->lotRepo->findById($id);
            if (! $lot) {
                return response()->json(['message' => 'Lot not found.'], 404);
            }
            return response()->json(['data' => $lot]);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Lot not found.'], 404);
        }
    }

    public function block(string $id): JsonResponse
    {
        try {
            $lot = $this->blockLot->execute(['lot_id' => $id]);
            return response()->json(['data' => $lot]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
