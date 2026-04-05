<?php
declare(strict_types=1);
namespace Modules\Warehouse\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
class WarehouseController extends Controller {
    public function __construct(private readonly WarehouseServiceInterface $service) {}
    public function index(Request $r): JsonResponse { return response()->json($this->service->findByTenant((int)$r->input('tenant_id'))); }
    public function show(int $id): JsonResponse { return response()->json($this->service->findById($id)); }
    public function store(Request $r): JsonResponse { return response()->json($this->service->create($r->all()),201); }
    public function update(Request $r, int $id): JsonResponse { return response()->json($this->service->update($id,$r->all())); }
    public function destroy(int $id): JsonResponse { $this->service->delete($id); return response()->json(null,204); }
}
