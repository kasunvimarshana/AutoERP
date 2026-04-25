<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\SearchProductCatalogServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Infrastructure\Http\Requests\SearchProductCatalogRequest;

class ProductSearchController extends AuthorizedController
{
    public function __construct(private readonly SearchProductCatalogServiceInterface $searchProductCatalogService) {}

    public function index(SearchProductCatalogRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $payload = $this->searchProductCatalogService->execute($request->validated());

        return response()->json($payload);
    }
}
