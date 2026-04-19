<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\CreateTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantDomainsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantDomainServiceInterface;
use Modules\Tenant\Application\DTOs\TenantDomainData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Entities\TenantDomain;
use Modules\Tenant\Infrastructure\Http\Requests\ListTenantDomainRequest;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantDomainRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantDomainRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantDomainCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantDomainResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantDomainController extends AuthorizedController
{
    public function __construct(
        private readonly FindTenantServiceInterface $findTenantService,
        private readonly FindTenantDomainsServiceInterface $findTenantDomainsService,
        private readonly CreateTenantDomainServiceInterface $createTenantDomainService,
        private readonly UpdateTenantDomainServiceInterface $updateTenantDomainService,
        private readonly DeleteTenantDomainServiceInterface $deleteTenantDomainService,
    ) {}

    public function index(int $tenant, ListTenantDomainRequest $request): TenantDomainCollection
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('view', $tenantEntity);

        $validated = $request->validated();
        $isPrimary = array_key_exists('is_primary', $validated)
            ? $request->boolean('is_primary')
            : null;
        $isVerified = array_key_exists('is_verified', $validated)
            ? $request->boolean('is_verified')
            : null;

        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);

        $tenantDomains = $this->findTenantDomainsService->paginateByTenant(
            tenantId: $tenant,
            isVerified: $isVerified,
            isPrimary: $isPrimary,
            perPage: $perPage,
            page: $page,
        );

        return new TenantDomainCollection($tenantDomains);
    }

    public function show(int $tenant, int $domain): TenantDomainResource
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('view', $tenantEntity);

        $tenantDomain = $this->findTenantDomainOrFail($tenant, $domain);

        return new TenantDomainResource($tenantDomain);
    }

    public function store(int $tenant, StoreTenantDomainRequest $request): JsonResponse
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('updateConfig', $tenantEntity);

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant;

        $dto = TenantDomainData::fromArray($payload);
        $created = $this->createTenantDomainService->execute($dto->toArray());

        return (new TenantDomainResource($created))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(int $tenant, int $domain, UpdateTenantDomainRequest $request): TenantDomainResource
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('updateConfig', $tenantEntity);

        $this->findTenantDomainOrFail($tenant, $domain);

        $payload = $request->validated();
        $payload['id'] = $domain;

        $updated = $this->updateTenantDomainService->execute($payload);

        return new TenantDomainResource($updated);
    }

    public function destroy(int $tenant, int $domain): JsonResponse
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('updateConfig', $tenantEntity);

        $this->findTenantDomainOrFail($tenant, $domain);
        $this->deleteTenantDomainService->execute(['id' => $domain]);

        return Response::json(['message' => 'Tenant domain deleted successfully']);
    }

    private function findTenantOrFail(int $tenantId): Tenant
    {
        $tenant = $this->findTenantService->find($tenantId);
        if (! $tenant) {
            throw new NotFoundHttpException('Tenant not found.');
        }

        return $tenant;
    }

    private function findTenantDomainOrFail(int $tenantId, int $tenantDomainId): TenantDomain
    {
        $tenantDomain = $this->findTenantDomainsService->find($tenantDomainId);

        if (! $tenantDomain || $tenantDomain->getTenantId() !== $tenantId) {
            throw new NotFoundHttpException('Tenant domain not found.');
        }

        return $tenantDomain;
    }
}
