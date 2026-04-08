<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\Contracts\OrgUnitServiceInterface;
use Modules\Configuration\Infrastructure\Http\Resources\OrgUnitResource;

class OrgUnitController extends Controller
{
    public function __construct(
        private readonly OrgUnitServiceInterface $orgUnitService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $units = $this->orgUnitService->getAllOrgUnits($request->user()->tenant_id);

        return response()->json(OrgUnitResource::collection(collect($units)));
    }

    public function show(Request $request, string $orgUnit): JsonResponse
    {
        $unit = $this->orgUnitService->getOrgUnit($request->user()->tenant_id, $orgUnit);

        return response()->json(new OrgUnitResource($unit));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'type'      => 'sometimes|in:company,division,department,team,branch,region,site,warehouse',
            'code'      => 'required|string|max:50',
            'parent_id' => 'nullable|uuid',
            'is_active' => 'sometimes|boolean',
            'metadata'  => 'sometimes|array',
        ]);

        $unit = $this->orgUnitService->createOrgUnit($request->user()->tenant_id, $data);

        return response()->json(new OrgUnitResource($unit), 201);
    }

    public function update(Request $request, string $orgUnit): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'type'      => 'sometimes|in:company,division,department,team,branch,region,site,warehouse',
            'code'      => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
            'metadata'  => 'sometimes|array',
        ]);

        $unit = $this->orgUnitService->updateOrgUnit($request->user()->tenant_id, $orgUnit, $data);

        return response()->json(new OrgUnitResource($unit));
    }

    public function destroy(Request $request, string $orgUnit): JsonResponse
    {
        $this->orgUnitService->deleteOrgUnit($request->user()->tenant_id, $orgUnit);

        return response()->json(null, 204);
    }

    public function tree(Request $request): JsonResponse
    {
        $tree = $this->orgUnitService->getTree($request->user()->tenant_id);

        return response()->json($tree);
    }

    public function descendants(Request $request, string $orgUnit): JsonResponse
    {
        $units = $this->orgUnitService->getDescendants($request->user()->tenant_id, $orgUnit);

        return response()->json(OrgUnitResource::collection(collect($units)));
    }

    public function ancestors(Request $request, string $orgUnit): JsonResponse
    {
        $units = $this->orgUnitService->getAncestors($request->user()->tenant_id, $orgUnit);

        return response()->json(OrgUnitResource::collection(collect($units)));
    }

    public function move(Request $request, string $orgUnit): JsonResponse
    {
        $data = $request->validate([
            'parent_id' => 'nullable|uuid',
        ]);

        $unit = $this->orgUnitService->moveOrgUnit(
            $request->user()->tenant_id,
            $orgUnit,
            $data['parent_id'] ?? null,
        );

        return response()->json(new OrgUnitResource($unit));
    }
}
