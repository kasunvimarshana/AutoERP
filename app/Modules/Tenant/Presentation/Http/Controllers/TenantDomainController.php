<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\Contracts\UseCases\Domains\TenantDomainServiceInterface;
use Modules\Tenant\Presentation\Http\Requests\ListTenantDomainRequest;
use Modules\Tenant\Presentation\Http\Requests\UpsertTenantDomainRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantDomainResource;

final class TenantDomainController extends Controller
{
    public function __construct(private readonly TenantDomainServiceInterface $domains)
    {
    }

    public function index(ListTenantDomainRequest $request): JsonResponse
    {
        $result = $this->domains->listByTenant((int) $request->validated('tenant_id'));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => TenantDomainResource::collection($result->valueOrFail())->resolve()]);
    }

    public function show(int|string $tenantDomain): JsonResponse|TenantDomainResource
    {
        $result = $this->domains->get($tenantDomain);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TenantDomainResource($result->valueOrFail());
    }

    public function store(UpsertTenantDomainRequest $request): JsonResponse|TenantDomainResource
    {
        $result = $this->domains->create($request->validated());
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TenantDomainResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertTenantDomainRequest $request, int|string $tenantDomain): JsonResponse|TenantDomainResource
    {
        $result = $this->domains->update($tenantDomain, $request->validated());
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'TENANT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new TenantDomainResource($result->valueOrFail());
    }

    public function destroy(int|string $tenantDomain): JsonResponse
    {
        $result = $this->domains->delete($tenantDomain);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
