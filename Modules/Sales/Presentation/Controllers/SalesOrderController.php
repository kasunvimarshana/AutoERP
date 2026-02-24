<?php
namespace Modules\Sales\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Application\UseCases\ConfirmOrderUseCase;
use Modules\Sales\Application\UseCases\CancelOrderUseCase;
use Modules\Sales\Infrastructure\Repositories\SalesOrderRepository;
use Modules\Sales\Presentation\Requests\StoreSalesOrderRequest;
use Modules\Shared\Application\ResponseFormatter;
class SalesOrderController extends Controller
{
    public function __construct(
        private ConfirmOrderUseCase $confirmUseCase,
        private CancelOrderUseCase $cancelUseCase,
        private SalesOrderRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $order = $this->repo->create($request->validated());
        return ResponseFormatter::success($order, 'Sales order created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $order = $this->repo->findById($id);
        if (!$order) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($order);
    }
    public function update(StoreSalesOrderRequest $request, string $id): JsonResponse
    {
        $order = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($order, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
    public function confirm(string $id): JsonResponse
    {
        $order = $this->confirmUseCase->execute($id);
        return ResponseFormatter::success($order, 'Order confirmed.');
    }
    public function cancel(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $order = $this->cancelUseCase->execute($id, $request->input('reason'));
        return ResponseFormatter::success($order, 'Order cancelled.');
    }
    public function ship(string $id): JsonResponse
    {
        $order = $this->repo->update($id, ['status' => 'shipped', 'shipped_at' => now()]);
        return ResponseFormatter::success($order, 'Order marked as shipped.');
    }
}
