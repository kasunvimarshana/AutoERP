<?php
namespace Modules\Sales\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Application\UseCases\AddPriceListItemUseCase;
use Modules\Sales\Application\UseCases\CreatePriceListUseCase;
use Modules\Sales\Application\UseCases\ResolvePriceForProductUseCase;
use Modules\Sales\Domain\Contracts\PriceListRepositoryInterface;
use Modules\Sales\Presentation\Requests\AddPriceListItemRequest;
use Modules\Sales\Presentation\Requests\StorePriceListRequest;
use Modules\Shared\Application\ResponseFormatter;

class PriceListController extends Controller
{
    public function __construct(
        private CreatePriceListUseCase       $createUseCase,
        private AddPriceListItemUseCase      $addItemUseCase,
        private ResolvePriceForProductUseCase $resolveUseCase,
        private PriceListRepositoryInterface  $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StorePriceListRequest $request): JsonResponse
    {
        $priceList = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($priceList, 'Price list created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $priceList = $this->repo->findById($id);
        if (!$priceList) {
            return ResponseFormatter::error('Price list not found.', [], 404);
        }
        return ResponseFormatter::success($priceList);
    }

    public function update(StorePriceListRequest $request, string $id): JsonResponse
    {
        $priceList = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($priceList, 'Price list updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Price list deleted.');
    }

    public function addItem(AddPriceListItemRequest $request, string $id): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => $request->user()?->tenant_id,
        ]);
        $item = $this->addItemUseCase->execute($id, $data);
        return ResponseFormatter::success($item, 'Item added to price list.', 201);
    }

    public function resolvePrice(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|uuid',
            'variant_id' => 'nullable|uuid',
            'qty'        => 'required|numeric|min:0.00000001',
            'base_price' => 'required|numeric|min:0',
        ]);

        $resolved = $this->resolveUseCase->execute(
            $id,
            $request->input('product_id'),
            $request->input('variant_id'),
            (string) $request->input('qty'),
            (string) $request->input('base_price'),
        );

        return ResponseFormatter::success(['resolved_price' => $resolved], 'Price resolved.');
    }
}
