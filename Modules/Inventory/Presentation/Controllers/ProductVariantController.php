<?php

namespace Modules\Inventory\Presentation\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\UseCases\CreateProductVariantUseCase;
use Modules\Inventory\Application\UseCases\UpdateProductVariantUseCase;
use Modules\Inventory\Domain\Contracts\ProductVariantRepositoryInterface;
use Modules\Inventory\Presentation\Requests\StoreProductVariantRequest;
use Modules\Shared\Application\ResponseFormatter;

class ProductVariantController extends Controller
{
    public function __construct(
        private CreateProductVariantUseCase $createUseCase,
        private UpdateProductVariantUseCase $updateUseCase,
        private ProductVariantRepositoryInterface $repo,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = request()->header('X-Tenant-ID', '');
        $filters  = request()->only(['product_id', 'is_active', 'search']);
        return ResponseFormatter::paginated($this->repo->paginate($tenantId, $filters, 20));
    }

    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        try {
            $variant = $this->createUseCase->execute($request->validated());
            return ResponseFormatter::success($variant, 'Product variant created.', 201);
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $variant = $this->repo->findById($id);
        if (!$variant) {
            return ResponseFormatter::error('Not found.', [], 404);
        }
        return ResponseFormatter::success($variant);
    }

    public function update(StoreProductVariantRequest $request, string $id): JsonResponse
    {
        try {
            $variant = $this->updateUseCase->execute($id, $request->validated());
            return ResponseFormatter::success($variant, 'Updated.');
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->repo->delete($id);
            return ResponseFormatter::success(null, 'Deleted.');
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }
}
