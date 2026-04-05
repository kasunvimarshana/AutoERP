<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
class GoodsReceiptController extends Controller {
    public function __construct(
        private readonly GoodsReceiptRepositoryInterface $repo,
        private readonly InspectGoodsReceiptServiceInterface $inspectService,
        private readonly PutAwayGoodsReceiptServiceInterface $putAwayService,
    ) {}
    public function index(Request $request): JsonResponse { return response()->json($this->repo->findByTenant((int)$request->input('tenant_id'))); }
    public function show(int $id): JsonResponse { $gr=$this->repo->findById($id); return response()->json($gr??['message'=>'Not found'],$gr?200:404); }
    public function store(Request $request): JsonResponse {
        $gr=$this->repo->create(array_merge($request->except('lines'),['status'=>'pending']),$request->input('lines',[]));
        return response()->json($gr,201);
    }
    public function inspect(Request $request, int $id): JsonResponse { return response()->json($this->inspectService->execute($id,(int)$request->input('inspected_by',0))); }
    public function putAway(Request $request, int $id): JsonResponse { return response()->json($this->putAwayService->execute($id,(int)$request->input('put_away_by',0))); }
    public function destroy(int $id): JsonResponse { $this->repo->delete($id); return response()->json(null,204); }
}
