<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\DTOs\TenantDomainData;
use Modules\Tenant\Application\Services\TenantService;
use Modules\Tenant\Domain\Exceptions\TenantRecordNotFoundException;
use Modules\Tenant\Presentation\Http\Controllers\Concerns\HandlesTenantHttp;
use Modules\Tenant\Presentation\Http\Requests\StoreTenantDomainRequest;
use Modules\Tenant\Presentation\Http\Requests\UpdateTenantDomainRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantDomainResource;

class TenantDomainController extends Controller
{
    use HandlesTenantHttp;

    public function __construct(private readonly TenantService $tenants) {}

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return TenantDomainResource::collection($this->tenants->listDomains($tenant, $this->perPage($request)));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreTenantDomainRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $domain = $this->tenants->createDomain(TenantDomainData::fromArray($tenant, $request->validated()));

            return (new TenantDomainResource($domain))->response()->setStatusCode(201);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $domain): TenantDomainResource|JsonResponse
    {
        try {
            return new TenantDomainResource($this->tenants->findDomain($tenant, $domain));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateTenantDomainRequest $request, int|string $tenant, int|string $domain): TenantDomainResource|JsonResponse
    {
        try {
            return new TenantDomainResource($this->tenants->updateDomain($tenant, $domain, TenantDomainData::fromArray($tenant, $request->validated())));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $domain): JsonResponse
    {
        try {
            $this->tenants->deleteDomain($tenant, $domain);

            return response()->json(null, 204);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
