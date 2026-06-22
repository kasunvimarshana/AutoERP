<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantPermission;
use Modules\Tenant\Http\Requests\ListTenantRequest;
use Modules\Tenant\Http\Requests\TenantLifecycleRequest;
use Modules\Tenant\Http\Requests\UpsertTenantRequest;
use Modules\Tenant\Http\Resources\TenantResource;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Services\ActivateTenantService;
use Modules\Tenant\Services\ArchiveTenantService;
use Modules\Tenant\Services\CreateTenantService;
use Modules\Tenant\Services\DeactivateTenantService;
use Modules\Tenant\Services\GetTenantService;
use Modules\Tenant\Services\ListTenantsService;
use Modules\Tenant\Services\SuspendTenantService;
use Modules\Tenant\Services\TenantAuthorizationService;
use Modules\Tenant\Services\UpdateTenantService;

final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantAuthorizationService $authorization,
        private readonly ListTenantsService $listTenants,
        private readonly GetTenantService $getTenant,
        private readonly CreateTenantService $createTenant,
        private readonly UpdateTenantService $updateTenant,
        private readonly ActivateTenantService $activateTenant,
        private readonly SuspendTenantService $suspendTenant,
        private readonly DeactivateTenantService $deactivateTenant,
        private readonly ArchiveTenantService $archiveTenant,
    ) {}

    public function index(ListTenantRequest $request): JsonResponse
    {
        $this->requirePermission(TenantPermission::PLATFORM_VIEW);

        $result = $this->listTenants->execute($request->validated());
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        $page = $result->valueOrFail();
        abort_unless($page instanceof PagedResult, 500, 'Unexpected tenant list response.');

        return response()->json([
            'data' => TenantResource::collection($page->items)->resolve(),
            'meta' => $page->paginationMeta(),
        ]);
    }

    public function show(int|string $tenant): JsonResponse|TenantResource
    {
        $this->requirePermission(TenantPermission::PLATFORM_VIEW);

        return $this->tenantResponse($this->getTenant->execute($tenant));
    }

    public function store(UpsertTenantRequest $request): JsonResponse|TenantResource
    {
        $this->requirePermission(TenantPermission::PLATFORM_MANAGE);

        $result = $this->createTenant->execute($this->payload($request));
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        return (new TenantResource($result->valueOrFail()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpsertTenantRequest $request,
        int|string $tenant,
    ): JsonResponse|TenantResource {
        $this->requirePermission(TenantPermission::PLATFORM_MANAGE);

        return $this->tenantResponse(
            $this->updateTenant->execute($tenant, $this->payload($request)),
        );
    }

    public function activate(
        TenantLifecycleRequest $request,
        int|string $tenant,
    ): JsonResponse|TenantResource {
        $this->requirePermission(TenantPermission::PLATFORM_MANAGE_LIFECYCLE);
        $data = $request->validated();

        return $this->tenantResponse($this->activateTenant->execute(
            $tenant,
            (int) $data['expected_version'],
            (string) $data['reason'],
        ));
    }

    public function suspend(
        TenantLifecycleRequest $request,
        int|string $tenant,
    ): JsonResponse|TenantResource {
        $this->requirePermission(TenantPermission::PLATFORM_MANAGE_LIFECYCLE);
        $data = $request->validated();

        return $this->tenantResponse($this->suspendTenant->execute(
            $tenant,
            (int) $data['expected_version'],
            (string) $data['reason'],
        ));
    }

    public function deactivate(
        TenantLifecycleRequest $request,
        int|string $tenant,
    ): JsonResponse|TenantResource {
        $this->requirePermission(TenantPermission::PLATFORM_MANAGE_LIFECYCLE);
        $data = $request->validated();

        return $this->tenantResponse($this->deactivateTenant->execute(
            $tenant,
            (int) $data['expected_version'],
            (string) $data['reason'],
        ));
    }

    public function archive(
        TenantLifecycleRequest $request,
        int|string $tenant,
    ): JsonResponse|TenantResource {
        $this->requirePermission(TenantPermission::PLATFORM_MANAGE_LIFECYCLE);
        $data = $request->validated();

        return $this->tenantResponse($this->archiveTenant->execute(
            $tenant,
            (int) $data['expected_version'],
            (string) $data['reason'],
        ));
    }

    /** @return array<string, mixed> */
    private function payload(UpsertTenantRequest $request): array
    {
        $payload = $request->validated();
        $file = $request->file('logo');
        unset($payload['logo']);

        if ($file instanceof UploadedFile) {
            $payload['logo_tmp_path'] = $file->getRealPath();
            $payload['logo_original_name'] = $file->getClientOriginalName();
        }

        return $payload;
    }

    private function tenantResponse(Result $result): JsonResponse|TenantResource
    {
        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantResource($result->valueOrFail());
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
