<?php
namespace Modules\Inventory\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\ReconcileInventoryServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;

class InventoryCycleCountController extends Controller
{
    public function __construct(
        private readonly InventoryCycleCountRepositoryInterface $repository,
        private readonly CreateCycleCountServiceInterface $createService,
        private readonly ReconcileInventoryServiceInterface $reconcileService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $counts = $this->repository->findAll($tenantId, $request->only(['warehouse_id', 'status', 'method']));
        return response()->json($counts);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id'    => 'required|integer',
            'warehouse_id' => 'required|integer',
            'method'       => 'required|string',
            'status'       => 'sometimes|string',
        ]);

        $count = $this->createService->execute($request->all());
        return response()->json($count, 201);
    }

    public function reconcile(Request $request, int $id): JsonResponse
    {
        $request->validate(['reconciled_by' => 'required|integer']);

        try {
            $count = $this->reconcileService->execute($id, (int) $request->input('reconciled_by'));
            return response()->json($count);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
