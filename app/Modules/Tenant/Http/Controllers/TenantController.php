<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Http\Requests\ListTenantRequest;
use Modules\Tenant\Http\Requests\UpsertTenantRequest;
use Modules\Tenant\Http\Resources\TenantResource;
use Modules\Tenant\Services\ActivateTenantService;
use Modules\Tenant\Services\CreateTenantService;
use Modules\Tenant\Services\DeactivateTenantService;
use Modules\Tenant\Services\GetTenantService;
use Modules\Tenant\Services\ListTenantsService;
use Modules\Tenant\Services\SuspendTenantService;
use Modules\Tenant\Services\UpdateTenantService;

final class TenantController extends Controller
{
    public function __construct(
        private readonly ListTenantsService $listTenants,
        private readonly GetTenantService $getTenant,
        private readonly CreateTenantService $createTenant,
        private readonly UpdateTenantService $updateTenant,
        private readonly ActivateTenantService $activateTenant,
        private readonly SuspendTenantService $suspendTenant,
        private readonly DeactivateTenantService $deactivateTenant,
    ) {}

    public function index(ListTenantRequest $request): JsonResponse
    {
        $result = $this->listTenants->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => TenantResource::collection($page->items)->resolve(),
            'meta' => $page->paginationMeta(),
        ]);
    }

    public function show(int|string $tenant): JsonResponse|TenantResource
    {
        $result = $this->getTenant->execute($tenant);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TenantResource($result->valueOrFail());
    }

    public function store(UpsertTenantRequest $request): JsonResponse|TenantResource
    {
        $result = $this->createTenant->execute($this->prepareMutationPayload($request));

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TenantResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertTenantRequest $request, int|string $tenant): JsonResponse|TenantResource
    {
        $result = $this->updateTenant->execute($tenant, $this->prepareMutationPayload($request));

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'TENANT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TenantResource($result->valueOrFail());
    }

    public function activate(int|string $tenant): JsonResponse|TenantResource
    {
        $result = $this->activateTenant->execute($tenant);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'TENANT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TenantResource($result->valueOrFail());
    }

    public function deactivate(int|string $tenant): JsonResponse|TenantResource
    {
        $result = $this->deactivateTenant->execute($tenant);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'TENANT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TenantResource($result->valueOrFail());
    }

    public function suspend(int|string $tenant): JsonResponse|TenantResource
    {
        $result = $this->suspendTenant->execute($tenant);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'TENANT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TenantResource($result->valueOrFail());
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareMutationPayload(UpsertTenantRequest $request): array
    {
        $payload = $request->validated();
        $logoUpload = $request->file('logo_path');

        if ($logoUpload instanceof UploadedFile) {
            unset($payload['logo_path']);

            $payload['logo_tmp_path'] = $logoUpload->getRealPath();
            $payload['logo_original_name'] = $logoUpload->getClientOriginalName();
        }

        return $payload;
    }
}
