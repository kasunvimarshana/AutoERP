<?php

namespace Modules\ECommerce\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ECommerce\Domain\Contracts\ProductListingRepositoryInterface;
use Modules\ECommerce\Presentation\Requests\StoreProductListingRequest;

class ProductListingController extends Controller
{
    public function __construct(
        private ProductListingRepositoryInterface $repo,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->repo->findByTenant(auth()->user()?->tenant_id));
    }

    public function catalog(): JsonResponse
    {
        return response()->json($this->repo->findActive(auth()->user()?->tenant_id));
    }

    public function store(StoreProductListingRequest $request): JsonResponse
    {
        $listing = $this->repo->create(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($listing, 201);
    }

    public function show(string $id): JsonResponse
    {
        $listing = $this->repo->findById($id);

        if (! $listing) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($listing);
    }

    public function update(StoreProductListingRequest $request, string $id): JsonResponse
    {
        return response()->json($this->repo->update($id, $request->validated()));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);

        return response()->json(null, 204);
    }
}
