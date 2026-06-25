<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Tenant\Constants\TenantPermission;
use Modules\Tenant\Http\Requests\UpdateTenantProfileRequest;
use Modules\Tenant\Http\Resources\TenantResource;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Services\GetTenantService;
use Modules\Tenant\Services\TenantAuthorizationService;
use Modules\Tenant\Services\UpdateTenantService;

final class TenantProfileController extends Controller
{
    public function __construct(
        private readonly TenantAuthorizationService $authorization,
        private readonly CurrentTenantContextAccessorInterface $context,
        private readonly GetTenantService $getTenant,
        private readonly UpdateTenantService $updateTenant,
    ) {}

    public function show(): JsonResponse|TenantResource
    {
        $this->requirePermission(TenantPermission::PROFILE_VIEW);

        $result = $this->getTenant->execute($this->tenantId());

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantResource($result->valueOrFail());
    }

    public function update(UpdateTenantProfileRequest $request): JsonResponse|TenantResource
    {
        $this->requirePermission(TenantPermission::PROFILE_MANAGE);

        $payload = $request->validated();
        $file = $request->file('logo');
        unset($payload['logo']);

        if ($file instanceof UploadedFile) {
            $payload['logo_tmp_path'] = $file->getRealPath();
        }

        $result = $this->updateTenant->execute($this->tenantId(), $payload);

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantResource($result->valueOrFail());
    }

    private function tenantId(): int
    {
        return (int) $this->context->requireCurrent()->tenantId();
    }

    private function requirePermission(string $permission): void
    {
        abort_unless(
            $this->authorization->allows($permission),
            403,
            'You are not authorized to perform this action.',
        );
    }
}
