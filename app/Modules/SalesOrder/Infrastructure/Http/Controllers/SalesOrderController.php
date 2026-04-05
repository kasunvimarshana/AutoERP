<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SalesOrder\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPickingSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
class SalesOrderController extends Controller {
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repo,
        private readonly CreateSalesOrderServiceInterface $createService,
        private readonly ConfirmSalesOrderServiceInterface $confirmService,
        private readonly StartPickingSalesOrderServiceInterface $pickingService,
        private readonly StartPackingSalesOrderServiceInterface $packingService,
    ) {}
    public function index(Request $request): JsonResponse { return response()->json($this->repo->findByTenant((int)$request->input('tenant_id'))); }
    public function show(int $id): JsonResponse { $so=$this->repo->findById($id); return response()->json($so??['message'=>'Not found'],$so?200:404); }
    public function store(Request $request): JsonResponse { return response()->json($this->createService->execute($request->except('lines'),$request->input('lines',[])),201); }
    public function confirm(int $id): JsonResponse { return response()->json($this->confirmService->execute($id)); }
    public function startPicking(int $id): JsonResponse { return response()->json($this->pickingService->execute($id)); }
    public function startPacking(int $id): JsonResponse { return response()->json($this->packingService->execute($id)); }
    public function destroy(int $id): JsonResponse { $this->repo->delete($id); return response()->json(null,204); }
}
