<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Infrastructure\Http\Resources\ProductResource;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;

class ProductController extends BaseController
{
    public function __construct(ProductServiceInterface $service)
    {
        parent::__construct($service, ProductResource::class, ProductData::class);
    }

    protected function getModelClass(): string
    {
        return ProductModel::class;
    }

    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['type', 'status', 'category_id']));
        $paginator = $this->service->list(
            $filters,
            $request->integer('per_page', 15),
            $request->integer('page', 1),
            $request->input('sort'),
            $request->input('include'),
        );

        return ProductResource::collection($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var \Modules\Product\Application\Contracts\ProductServiceInterface $service */
        $service = $this->service;
        $product = $service->createProduct($request->all());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        return (new ProductResource($this->service->find($id)))->response();
    }

    public function update(Request $request, string $id): JsonResponse
    {
        /** @var \Modules\Product\Application\Contracts\ProductServiceInterface $service */
        $service = $this->service;

        return (new ProductResource($service->updateProduct($id, $request->all())))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
