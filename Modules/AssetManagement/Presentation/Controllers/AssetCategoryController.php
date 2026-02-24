<?php

namespace Modules\AssetManagement\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\AssetManagement\Application\UseCases\CreateAssetCategoryUseCase;
use Modules\AssetManagement\Domain\Contracts\AssetCategoryRepositoryInterface;
use Modules\AssetManagement\Presentation\Requests\StoreAssetCategoryRequest;

class AssetCategoryController extends Controller
{
    public function __construct(
        private AssetCategoryRepositoryInterface $categoryRepo,
        private CreateAssetCategoryUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->categoryRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreAssetCategoryRequest $request): JsonResponse
    {
        $category = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($category, 201);
    }

    public function show(string $id): JsonResponse
    {
        $category = $this->categoryRepo->findById($id);

        if (! $category) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($category);
    }

    public function update(StoreAssetCategoryRequest $request, string $id): JsonResponse
    {
        $category = $this->categoryRepo->update($id, $request->validated());

        return response()->json($category);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->categoryRepo->delete($id);

        return response()->json(null, 204);
    }
}
