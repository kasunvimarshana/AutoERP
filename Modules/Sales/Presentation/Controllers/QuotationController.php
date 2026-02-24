<?php
namespace Modules\Sales\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Sales\Application\UseCases\CreateQuotationUseCase;
use Modules\Sales\Application\UseCases\ConvertQuotationToOrderUseCase;
use Modules\Sales\Infrastructure\Repositories\QuotationRepository;
use Modules\Sales\Presentation\Requests\StoreQuotationRequest;
use Modules\Shared\Application\ResponseFormatter;
class QuotationController extends Controller
{
    public function __construct(
        private CreateQuotationUseCase $createUseCase,
        private ConvertQuotationToOrderUseCase $convertUseCase,
        private QuotationRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreQuotationRequest $request): JsonResponse
    {
        $quotation = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($quotation, 'Quotation created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $quotation = $this->repo->findById($id);
        if (!$quotation) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($quotation);
    }
    public function update(StoreQuotationRequest $request, string $id): JsonResponse
    {
        $quotation = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($quotation, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
    public function convertToOrder(string $id): JsonResponse
    {
        $order = $this->convertUseCase->execute($id);
        return ResponseFormatter::success($order, 'Quotation converted to sales order.');
    }
}
