<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PurchaseOrder\Application\Contracts\ConfirmPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
class PurchaseOrderController extends Controller {
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repo,
        private readonly CreatePurchaseOrderServiceInterface $createService,
        private readonly ConfirmPurchaseOrderServiceInterface $confirmService,
    ) {}
    public function index(Request $request): JsonResponse {
        return response()->json($this->repo->findByTenant((int)$request->input('tenant_id'),[],(int)$request->input('per_page',15),(int)$request->input('page',1)));
    }
    public function show(int $id): JsonResponse { $po=$this->repo->findById($id); return response()->json($po??['message'=>'Not found'],$po?200:404); }
    public function store(Request $request): JsonResponse {
        return response()->json($this->createService->execute($request->except('lines'),$request->input('lines',[])),201);
    }
    public function confirm(int $id): JsonResponse { return response()->json($this->confirmService->execute($id)); }
    public function destroy(int $id): JsonResponse { $this->repo->delete($id); return response()->json(null,204); }
}
