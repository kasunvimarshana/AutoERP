<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PartyManagement\Application\Contracts\ManageAssetOwnershipServiceInterface;
use Modules\PartyManagement\Infrastructure\Http\Requests\CreateAssetOwnershipRequest;
use Modules\PartyManagement\Infrastructure\Http\Resources\AssetOwnershipResource;

class AssetOwnershipController extends Controller
{
    public function __construct(
        private readonly ManageAssetOwnershipServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $perPage  = (int) ($request->query('per_page', 15));
        $page     = (int) ($request->query('page', 1));

        $result = $this->service->list($tenantId, $perPage, $page);

        return response()->json($result);
    }

    public function store(CreateAssetOwnershipRequest $request): JsonResponse
    {
        $ownership = $this->service->create($request->validated());

        return response()->json(new AssetOwnershipResource($ownership), 201);
    }

    public function show(Request $request, string $assetOwnership): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $result   = $this->service->find($tenantId, $assetOwnership);

        return response()->json(new AssetOwnershipResource($result));
    }

    public function update(Request $request, string $assetOwnership): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $result   = $this->service->update($tenantId, $assetOwnership, $request->all());

        return response()->json(new AssetOwnershipResource($result));
    }

    public function byParty(Request $request, string $party): JsonResponse
    {
        $tenantId  = (int) $request->header('X-Tenant-ID');
        $ownerships = $this->service->listByParty($tenantId, $party);

        return response()->json([
            'data' => array_map(
                fn ($o) => (new AssetOwnershipResource($o))->toArray($request),
                $ownerships
            ),
        ]);
    }

    public function byAsset(Request $request, string $asset): JsonResponse
    {
        $tenantId  = (int) $request->header('X-Tenant-ID');
        $ownerships = $this->service->listByAsset($tenantId, $asset);

        return response()->json([
            'data' => array_map(
                fn ($o) => (new AssetOwnershipResource($o))->toArray($request),
                $ownerships
            ),
        ]);
    }
}
