<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\ListTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantConfigRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantConfigResource;
use Modules\Tenant\Infrastructure\Http\Resources\TenantResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantController extends AuthorizedController
{
    public function __construct(
        protected CreateTenantServiceInterface $createService,
        protected UpdateTenantServiceInterface $updateService,
        protected DeleteTenantServiceInterface $deleteService,
        protected UpdateTenantConfigServiceInterface $configService,
        protected FindTenantServiceInterface $findTenantService,
        protected UploadTenantAttachmentServiceInterface $uploadAttachmentService
    ) {}

    public function index(ListTenantRequest $request): TenantCollection
    {
        $this->authorize('viewAny', Tenant::class);
        $validated = $request->validated();

        $filters = array_filter([
            'name' => $validated['name'] ?? null,
            'slug' => $validated['slug'] ?? null,
            'domain' => $validated['domain'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        if (array_key_exists('active', $validated)) {
            $filters['active'] = $request->boolean('active');
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $page    = (int) ($validated['page'] ?? 1);
        $sort    = $validated['sort'] ?? null;
        $include = $validated['include'] ?? null;

        $tenants = $this->findTenantService->list($filters, $perPage, $page, $sort, $include);

        return new TenantCollection($tenants);
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);
        $dto = TenantData::fromArray($request->validated());
        $tenant = $this->createService->execute($dto->toArray());

        if ($request->hasFile('logo')) {
            $this->uploadAttachmentService->execute([
                'tenant_id' => $tenant->getId(),
                'file'      => $request->file('logo'),
                'type'      => 'logo',
            ]);
            $tenant = $this->findTenantService->find($tenant->getId()) ?? $tenant;
        }

        return (new TenantResource($tenant))->response()->setStatusCode(201);
    }

    public function show(int $tenant): TenantResource
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('view', $tenantEntity);

        return new TenantResource($tenantEntity);
    }

    public function update(UpdateTenantRequest $request, int $tenant): TenantResource
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('update', $tenantEntity);

        $payload = $request->validated();
        $payload['id'] = $tenant;
        $updated = $this->updateService->execute($payload);

        if ($request->hasFile('logo')) {
            $this->uploadAttachmentService->execute([
                'tenant_id' => $tenant,
                'file'      => $request->file('logo'),
                'type'      => 'logo',
            ]);

            $updated = $this->findTenantService->find($tenant) ?? $updated;
        }

        return new TenantResource($updated);
    }

    public function updateConfig(UpdateTenantConfigRequest $request, int $tenant): TenantConfigResource
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('updateConfig', $tenantEntity);

        $validated = $request->validated();
        $validated['id'] = $tenant;
        $updated = $this->configService->execute($validated);

        return new TenantConfigResource($updated);
    }

    public function destroy(int $tenant): JsonResponse
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('delete', $tenantEntity);
        $this->deleteService->execute(['id' => $tenant]);

        return Response::json(['message' => 'Tenant deleted successfully']);
    }

    public function configByDomain(string $domain): TenantConfigResource
    {
        $tenant = $this->findTenantService->findByDomain($domain);
        if (! $tenant) {
            throw new NotFoundHttpException('Tenant not found for the requested domain.');
        }

        return new TenantConfigResource($tenant);
    }

    private function findTenantOrFail(int $tenantId): Tenant
    {
        $tenant = $this->findTenantService->find($tenantId);
        if (! $tenant) {
            throw new NotFoundHttpException('Tenant not found.');
        }

        return $tenant;
    }

}
