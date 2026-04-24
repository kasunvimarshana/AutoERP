<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\RebuildProductSearchProjectionServiceInterface;
use Modules\Product\Application\Contracts\SearchProductsServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Infrastructure\Http\Requests\RebuildProductSearchProjectionRequest;
use Modules\Product\Infrastructure\Http\Requests\SearchProductRequest;

class ProductSearchController extends AuthorizedController
{
    public function __construct(
        private readonly SearchProductsServiceInterface $searchProductsService,
        private readonly RebuildProductSearchProjectionServiceInterface $rebuildProjectionService,
    ) {}

    public function index(SearchProductRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $validated = $request->validated();
        $tenantId = (int) ($validated['tenant_id'] ?? ($request->user()?->tenant_id ?? $request->header('X-Tenant-ID', '0')));

        if ($tenantId <= 0) {
            return response()->json([
                'message' => 'A valid tenant_id is required.',
            ], 422);
        }

        $validated['tenant_id'] = $tenantId;
        $results = $this->searchProductsService->execute($validated);

        return response()->json($results);
    }

    public function rebuild(RebuildProductSearchProjectionRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $validated = $request->validated();
        $tenantId = (int) ($validated['tenant_id'] ?? ($request->user()?->tenant_id ?? $request->header('X-Tenant-ID', '0')));

        if ($tenantId <= 0) {
            return response()->json([
                'message' => 'A valid tenant_id is required.',
            ], 422);
        }

        $count = $this->rebuildProjectionService->execute($tenantId);

        return response()->json([
            'data' => [
                'tenant_id' => $tenantId,
                'indexed_rows' => $count,
            ],
        ]);
    }
}
