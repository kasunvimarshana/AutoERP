<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Product\Application\Contracts\ProductCategoryServiceInterface;
use Modules\Product\Application\DTOs\ProductCategoryData;
use Modules\Product\Infrastructure\Http\Resources\ProductCategoryResource;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryModel;

class ProductCategoryController extends BaseController
{
    public function __construct(ProductCategoryServiceInterface $service)
    {
        parent::__construct($service, ProductCategoryResource::class, ProductCategoryData::class);
    }

    protected function getModelClass(): string
    {
        return ProductCategoryModel::class;
    }

    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['parent_id', 'is_active']));
        $paginator = $this->service->list(
            $filters,
            $request->integer('per_page', 15),
            $request->integer('page', 1),
            $request->input('sort'),
        );

        return ProductCategoryResource::collection($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $category = $this->service->execute($request->all());

        return (new ProductCategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        return (new ProductCategoryResource($this->service->find($id)))->response();
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return (new ProductCategoryResource($this->service->update($id, $request->all())))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
