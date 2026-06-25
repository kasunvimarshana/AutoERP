<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\OrganizationUnit\Http\Requests\ListOrganizationUnitRequest;
use Modules\OrganizationUnit\Http\Resources\OrganizationUnitResource;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;

final class OrganizationUnitContextController extends Controller
{
    public function __construct(
        private readonly OrganizationUnitUserAccessCheckerInterface $access,
        private readonly OrganizationUnitRepositoryInterface $units,
    ) {}

    public function options(ListOrganizationUnitRequest $request): JsonResponse
    {
        $userId = $request->currentUserId();
        abort_unless($userId !== null, 401);
        $records = $this->units->listAccessibleByIds(
            $request->tenantId(),
            $this->access->accessibleOrganizationUnitIds($userId, $request->tenantId()),
        );

        return response()->json([
            'data' => OrganizationUnitResource::collection($records)->resolve($request),
            'default_organization_unit_ids' => $this->access->defaultOrganizationUnitIds($userId, $request->tenantId()),
            'current_organization_unit_id' => $request->organizationUnitId(),
        ]);
    }
}
