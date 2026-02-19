<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\CreateOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($this->organizationService->paginate($tenantId, $perPage));
    }

    public function tree(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        return response()->json($this->organizationService->tree($tenantId));
    }

    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        $org = $this->organizationService->create($data);

        return response()->json($org, 201);
    }

    public function update(UpdateOrganizationRequest $request, string $id): JsonResponse
    {
        $org = $this->organizationService->update($id, $request->validated());

        return response()->json($org);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('organizations.delete'), 403);
        $this->organizationService->delete($id);

        return response()->json(null, 204);
    }
}
