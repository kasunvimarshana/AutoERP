<?php
namespace Modules\Inventory\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\UseCases\CreateProductUseCase;
use Modules\Inventory\Infrastructure\Repositories\ProductRepository;
use Modules\Inventory\Presentation\Requests\StoreProductRequest;
use Modules\Shared\Application\ResponseFormatter;
class ProductController extends Controller
{
    public function __construct(
        private CreateProductUseCase $createUseCase,
        private ProductRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($product, 'Product created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $product = $this->repo->findById($id);
        if (!$product) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($product);
    }
    public function update(StoreProductRequest $request, string $id): JsonResponse
    {
        $product = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($product, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
}
