<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\OrganizationUnit\Constants\OrganizationUnitPermission;
use Modules\OrganizationUnit\Http\Requests\ListOrganizationUnitRequest;
use Modules\OrganizationUnit\Http\Requests\UpsertOrganizationUnitLegalProfileRequest;
use Modules\OrganizationUnit\Http\Resources\OrganizationUnitLegalProfileResource;
use Modules\OrganizationUnit\Services\Authorization\OrganizationUnitAuthorizationService;
use Modules\OrganizationUnit\Services\LegalProfile\OrganizationUnitLegalProfileService;

final class OrganizationUnitLegalProfileController
{
    public function __construct(
        private readonly OrganizationUnitLegalProfileService $profiles,
        private readonly OrganizationUnitAuthorizationService $authorization,
    ) {}

    public function show(ListOrganizationUnitRequest $request, int $organizationUnit): JsonResponse|OrganizationUnitLegalProfileResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::VIEW);
        $profile = $this->profiles->find($request->tenantId(), $organizationUnit);

        return $profile === null
            ? response()->json(['data' => null])
            : new OrganizationUnitLegalProfileResource($profile);
    }

    public function update(UpsertOrganizationUnitLegalProfileRequest $request, int $organizationUnit): OrganizationUnitLegalProfileResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::UPDATE);

        return new OrganizationUnitLegalProfileResource($this->profiles->upsert(
            $request->tenantId(),
            $organizationUnit,
            $request->validated(),
        ));
    }
}
