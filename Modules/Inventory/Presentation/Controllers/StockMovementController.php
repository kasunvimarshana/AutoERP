<?php
namespace Modules\Inventory\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\UseCases\ReceiveStockUseCase;
use Modules\Inventory\Application\UseCases\DeductStockUseCase;
use Modules\Inventory\Application\UseCases\TransferStockUseCase;
use Modules\Inventory\Application\UseCases\AdjustStockUseCase;
use Modules\Inventory\Infrastructure\Repositories\StockMovementRepository;
use Modules\Inventory\Presentation\Requests\StoreStockMovementRequest;
use Modules\Shared\Application\ResponseFormatter;
class StockMovementController extends Controller
{
    public function __construct(
        private ReceiveStockUseCase $receiveUseCase,
        private DeductStockUseCase $deductUseCase,
        private TransferStockUseCase $transferUseCase,
        private AdjustStockUseCase $adjustUseCase,
        private StockMovementRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function show(string $id): JsonResponse
    {
        $movement = $this->repo->findById($id);
        if (!$movement) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($movement);
    }
    public function store(StoreStockMovementRequest $request): JsonResponse
    {
        $data = $request->validated();
        $movement = match ($data['type']) {
            'receipt'    => $this->receiveUseCase->execute($data),
            'delivery'   => $this->deductUseCase->execute($data),
            'transfer'   => $this->transferUseCase->execute($data),
            'adjustment' => $this->adjustUseCase->execute($data),
            default      => throw new \InvalidArgumentException('Unknown movement type: '.$data['type']),
        };
        return ResponseFormatter::success($movement, 'Stock movement recorded.', 201);
    }
}
