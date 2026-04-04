<?php
namespace Modules\Product\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Application\Contracts\CreateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\ProductCategoryTreeServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Application\DTOs\ProductCategoryData;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Modules\Product\Infrastructure\Http\Resources\ProductCategoryResource;

class ProductCategoryController extends Controller
{
    public function __construct(
        private readonly ProductCategoryRepositoryInterface $repository,
        private readonly CreateProductCategoryServiceInterface $createService,
        private readonly UpdateProductCategoryServiceInterface $updateService,
        private readonly DeleteProductCategoryServiceInterface $deleteService,
        private readonly ProductCategoryTreeServiceInterface $treeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId   = (int) $request->query('tenant_id', 0);
        $filters    = $request->only(['is_active', 'parent_id']);
        $perPage    = (int) $request->query('per_page', 15);
        $categories = $this->repository->findAll($tenantId, $filters, $perPage);
        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $data = new ProductCategoryData(
            tenantId:    (int) $request->input('tenant_id'),
            name:        $request->input('name'),
            slug:        $request->input('slug'),
            parentId:    $request->input('parent_id') !== null ? (int) $request->input('parent_id') : null,
            description: $request->input('description'),
            isActive:    (bool) $request->input('is_active', true),
        );
        $category = $this->createService->execute($data);
        return response()->json(new ProductCategoryResource($category), 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new ProductCategoryResource($category));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = new ProductCategoryData(
            tenantId:    $category->tenantId,
            name:        $request->input('name', $category->name),
            slug:        $request->input('slug', $category->slug),
            parentId:    $request->has('parent_id') ? ($request->input('parent_id') !== null ? (int) $request->input('parent_id') : null) : $category->parentId,
            description: $request->input('description', $category->description),
            isActive:    $request->input('is_active', $category->isActive),
        );
        $updated = $this->updateService->execute($category, $data);
        return response()->json(new ProductCategoryResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($category);
        return response()->json(null, 204);
    }

    public function tree(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        return response()->json($this->treeService->buildTree($tenantId));
    }
}
