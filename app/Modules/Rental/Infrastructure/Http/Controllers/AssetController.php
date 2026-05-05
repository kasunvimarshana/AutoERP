<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\CreateAssetServiceInterface;
use Modules\Rental\Application\Contracts\FindAssetServiceInterface;
use Modules\Rental\Application\Contracts\UpdateAssetServiceInterface;
use Modules\Rental\Domain\Entities\Asset;
use Modules\Rental\Infrastructure\Http\Requests\StoreAssetRequest;
use Modules\Rental\Infrastructure\Http\Requests\UpdateAssetRequest;
use Modules\Rental\Infrastructure\Http\Resources\AssetResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AssetController extends AuthorizedController
{
    public function __construct(
        private readonly CreateAssetServiceInterface $createAsset,
        private readonly FindAssetServiceInterface $findAsset,
        private readonly UpdateAssetServiceInterface $updateAsset,
    ) {}

    public function index(StoreAssetRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $validated = $request->validated();
        $result = $this->findAsset->paginate(
            tenantId: (int) $validated['tenant_id'],
            filters: array_filter([
                'usage_mode' => $validated['usage_mode'] ?? null,
                'lifecycle_status' => $validated['lifecycle_status'] ?? null,
                'rental_status' => $validated['rental_status'] ?? null,
                'service_status' => $validated['service_status'] ?? null,
            ], static fn (mixed $v): bool => $v !== null),
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($result);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $this->authorize('create', Asset::class);

        $asset = $this->createAsset->execute($request->validated());

        return (new AssetResource($asset))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $asset): JsonResponse
    {
        $this->authorize('view', Asset::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $found = $this->findAsset->findById($tenantId, $asset);

        return (new AssetResource($found))->response();
    }

    public function update(UpdateAssetRequest $request, int $asset): JsonResponse
    {
        $this->authorize('update', Asset::class);

        $tenantId = (int) $request->validated()['tenant_id'];
        $updated = $this->updateAsset->execute($tenantId, $asset, $request->validated());

        return (new AssetResource($updated))->response();
    }

    public function destroy(int $asset): JsonResponse
    {
        $this->authorize('delete', Asset::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $found = $this->findAsset->findById($tenantId, $asset);
        $found->update(['lifecycle_status' => 'retired']);
        $this->updateAsset->execute($tenantId, $asset, ['lifecycle_status' => 'retired']);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
