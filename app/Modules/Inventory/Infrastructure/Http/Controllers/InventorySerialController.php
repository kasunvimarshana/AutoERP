<?php
namespace Modules\Inventory\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Application\Contracts\CreateInventorySerialServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialRepositoryInterface;
use Modules\Inventory\Infrastructure\Http\Resources\InventorySerialResource;

class InventorySerialController extends Controller
{
    public function __construct(
        private readonly InventorySerialRepositoryInterface $repository,
        private readonly CreateInventorySerialServiceInterface $createService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id'     => 'required|integer',
            'product_id'    => 'required|integer',
            'serial_number' => 'required|string',
            'status'        => 'sometimes|string',
        ]);

        $serial = $this->createService->execute($request->all());
        return response()->json(new InventorySerialResource($serial), 201);
    }

    public function show(int $id): JsonResponse
    {
        $serial = $this->repository->findById($id);
        if (!$serial) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new InventorySerialResource($serial));
    }
}
