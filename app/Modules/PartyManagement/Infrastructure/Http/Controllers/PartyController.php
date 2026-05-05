<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PartyManagement\Application\Contracts\ManagePartyServiceInterface;
use Modules\PartyManagement\Infrastructure\Http\Requests\CreatePartyRequest;
use Modules\PartyManagement\Infrastructure\Http\Requests\UpdatePartyRequest;
use Modules\PartyManagement\Infrastructure\Http\Resources\PartyResource;

class PartyController extends Controller
{
    public function __construct(
        private readonly ManagePartyServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $perPage  = (int) ($request->query('per_page', 15));
        $page     = (int) ($request->query('page', 1));

        $result = $this->service->list($tenantId, $perPage, $page);

        return response()->json($result);
    }

    public function store(CreatePartyRequest $request): JsonResponse
    {
        $party = $this->service->create($request->validated());

        return response()->json(new PartyResource($party), 201);
    }

    public function show(Request $request, string $party): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $result   = $this->service->find($tenantId, $party);

        return response()->json(new PartyResource($result));
    }

    public function update(UpdatePartyRequest $request, string $party): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $result   = $this->service->update($tenantId, $party, $request->validated());

        return response()->json(new PartyResource($result));
    }

    public function destroy(Request $request, string $party): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $this->service->delete($tenantId, $party);

        return response()->json(null, 204);
    }
}
