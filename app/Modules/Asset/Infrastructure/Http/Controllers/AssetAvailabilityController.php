<?php

declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Asset\Application\Contracts\FindAssetAvailabilityServiceInterface;
use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Asset\Infrastructure\Http\Requests\SyncAssetAvailabilityRequest;
use Modules\Asset\Infrastructure\Http\Resources\AssetAvailabilityStateResource;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AssetAvailabilityController extends AuthorizedController
{
    public function __construct(
        private readonly SyncAssetAvailabilityServiceInterface $syncService,
        private readonly FindAssetAvailabilityServiceInterface $findService,
    ) {}

    public function show(Request $request, int $asset): JsonResponse
    {
        $tenantId = (int) ($request->user()?->tenant_id ?? $request->header('X-Tenant-ID', '0'));

        $state = $this->findService->findCurrentState($tenantId, $asset);

        if ($state === null) {
            return Response::json(['message' => 'Asset availability state not found.'], HttpResponse::HTTP_NOT_FOUND);
        }

        return (new AssetAvailabilityStateResource($state))->response();
    }

    public function sync(SyncAssetAvailabilityRequest $request): AssetAvailabilityStateResource
    {
        return new AssetAvailabilityStateResource($this->syncService->execute($request->validated()));
    }
}
