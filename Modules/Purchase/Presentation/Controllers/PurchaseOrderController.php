<?php
namespace Modules\Purchase\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\UseCases\CreatePurchaseOrderUseCase;
use Modules\Purchase\Application\UseCases\ApprovePurchaseOrderUseCase;
use Modules\Purchase\Application\UseCases\ReceiveGoodsUseCase;
use Modules\Purchase\Infrastructure\Repositories\PurchaseOrderRepository;
use Modules\Purchase\Presentation\Requests\StorePurchaseOrderRequest;
use Modules\Purchase\Presentation\Requests\ReceiveGoodsRequest;
use Modules\Shared\Application\ResponseFormatter;
class PurchaseOrderController extends Controller
{
    public function __construct(
        private CreatePurchaseOrderUseCase $createUseCase,
        private ApprovePurchaseOrderUseCase $approveUseCase,
        private ReceiveGoodsUseCase $receiveUseCase,
        private PurchaseOrderRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $po = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($po, 'Purchase order created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $po = $this->repo->findById($id);
        if (!$po) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($po);
    }
    public function update(StorePurchaseOrderRequest $request, string $id): JsonResponse
    {
        $po = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($po, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
    public function approve(string $id): JsonResponse
    {
        $po = $this->approveUseCase->execute($id);
        return ResponseFormatter::success($po, 'Purchase order approved.');
    }
    public function receive(ReceiveGoodsRequest $request, string $id): JsonResponse
    {
        $grn = $this->receiveUseCase->execute($id, $request->validated());
        return ResponseFormatter::success($grn, 'Goods received.');
    }
}
