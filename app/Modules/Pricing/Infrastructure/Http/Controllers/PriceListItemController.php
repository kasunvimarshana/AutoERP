<?php
namespace Modules\Pricing\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListItemData;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListItemResource;

class PriceListItemController extends Controller
{
    public function __construct(
        private readonly PriceListItemRepositoryInterface $repository,
        private readonly CreatePriceListItemServiceInterface $createService,
        private readonly UpdatePriceListItemServiceInterface $updateService,
        private readonly DeletePriceListItemServiceInterface $deleteService,
    ) {}

    public function index(Request $request, int $id): JsonResponse
    {
        $items = $this->repository->findByPriceList($id);
        return response()->json(array_map(fn ($item) => new PriceListItemResource($item), $items));
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'product_id'       => ['required', 'integer'],
            'price'            => ['required', 'numeric', 'min:0'],
            'variant_id'       => ['nullable', 'integer'],
            'min_qty'          => ['nullable', 'numeric', 'min:0'],
            'max_qty'          => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'uom'              => ['sometimes', 'string', 'max:20'],
        ]);
        $data = new PriceListItemData(
            priceListId: $id,
            productId: $validated['product_id'],
            price: (float) $validated['price'],
            variantId: $validated['variant_id'] ?? null,
            minQty: isset($validated['min_qty']) ? (float) $validated['min_qty'] : null,
            maxQty: isset($validated['max_qty']) ? (float) $validated['max_qty'] : null,
            discountPercent: isset($validated['discount_percent']) ? (float) $validated['discount_percent'] : null,
            uom: $validated['uom'] ?? 'unit',
        );
        return response()->json(new PriceListItemResource($this->createService->execute($data)), 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->repository->findById($id);
        if (!$item) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new PriceListItemResource($item));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->repository->findById($id);
        if (!$item) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = $request->validate([
            'price'            => ['sometimes', 'numeric', 'min:0'],
            'variant_id'       => ['nullable', 'integer'],
            'min_qty'          => ['nullable', 'numeric', 'min:0'],
            'max_qty'          => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'uom'              => ['sometimes', 'string', 'max:20'],
        ]);
        return response()->json(new PriceListItemResource($this->updateService->execute($item, $data)));
    }

    public function destroy(int $id): JsonResponse
    {
        $item = $this->repository->findById($id);
        if (!$item) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($item);
        return response()->json(null, 204);
    }
}
