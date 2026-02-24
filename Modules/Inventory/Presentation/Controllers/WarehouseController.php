<?php
namespace Modules\Inventory\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Inventory\Infrastructure\Repositories\WarehouseRepository;
use Modules\Inventory\Presentation\Requests\StoreWarehouseRequest;
use Modules\Shared\Application\ResponseFormatter;
class WarehouseController extends Controller
{
    public function __construct(private WarehouseRepository $repo) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->repo->create($request->validated());
        return ResponseFormatter::success($warehouse, 'Warehouse created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $warehouse = $this->repo->findById($id);
        if (!$warehouse) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($warehouse);
    }
    public function update(StoreWarehouseRequest $request, string $id): JsonResponse
    {
        $warehouse = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($warehouse, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
}
