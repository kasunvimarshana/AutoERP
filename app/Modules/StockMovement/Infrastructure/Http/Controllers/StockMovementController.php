<?php
declare(strict_types=1);
namespace Modules\StockMovement\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
class StockMovementController extends Controller {
    public function __construct(private readonly StockMovementRepositoryInterface $repo) {}
    public function byProduct(Request $r): JsonResponse { return response()->json($this->repo->findByProduct((int)$r->input('tenant_id'),(int)$r->input('product_id'))); }
    public function byWarehouse(Request $r): JsonResponse { return response()->json($this->repo->findByWarehouse((int)$r->input('tenant_id'),(int)$r->input('warehouse_id'))); }
    public function store(Request $r): JsonResponse { return response()->json($this->repo->create($r->all()),201); }
}
