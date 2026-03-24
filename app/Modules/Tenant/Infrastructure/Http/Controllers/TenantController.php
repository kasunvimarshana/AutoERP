<?php

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Application\DTOs\TenantConfigData;
use Modules\Tenant\Infrastructure\Http\Resources\TenantResource;
use Modules\Tenant\Infrastructure\Http\Resources\TenantCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantConfigResource;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TenantController extends BaseController
{
    public function __construct(
        CreateTenantServiceInterface $createService,
        protected UpdateTenantServiceInterface $updateService,
        protected DeleteTenantServiceInterface $deleteService,
        protected UpdateTenantConfigServiceInterface $configService,
        protected TenantRepositoryInterface $tenantRepository
    ) {
        parent::__construct($createService, TenantResource::class, TenantData::class);
    }

    public function index(Request $request): TenantCollection
    {
        $this->authorize('viewAny', Tenant::class);
        $filters = $request->only(['name', 'domain', 'active']);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $sort = $request->input('sort');
        $include = $request->input('include');

        $tenants = $this->service->list($filters, $perPage, $page, $sort, $include);
        return new TenantCollection($tenants);
    }

    public function store(Request $request): TenantResource
    {
        $this->authorize('create', Tenant::class);
        $validated = $request->validate((new TenantData())->rules());
        $dto = TenantData::fromArray($validated);
        $tenant = $this->service->execute($dto->toArray());
        return new TenantResource($tenant);
    }

    public function show(int $id): TenantResource
    {
        $tenant = $this->service->find($id);
        if (!$tenant) {
            abort(404);
        }
        $this->authorize('view', $tenant);
        return new TenantResource($tenant);
    }

    public function update(Request $request, int $id): TenantResource
    {
        $tenant = $this->service->find($id);
        if (!$tenant) {
            abort(404);
        }
        $this->authorize('update', $tenant);
        $validated = $request->validate((new TenantData())->rules());
        $validated['id'] = $id;
        $dto = TenantData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());
        return new TenantResource($updated);
    }

    public function updateConfig(Request $request, int $id): TenantConfigResource
    {
        $tenant = $this->service->find($id);
        if (!$tenant) {
            abort(404);
        }
        $this->authorize('updateConfig', $tenant);
        $validated = $request->validate((new TenantConfigData())->rules());
        $validated['id'] = $id;
        $dto = TenantConfigData::fromArray($validated);
        $updated = $this->configService->execute($dto->toArray());
        return new TenantConfigResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $tenant = $this->service->find($id);
        if (!$tenant) {
            abort(404);
        }
        $this->authorize('delete', $tenant);
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Tenant deleted successfully']);
    }

    public function configByDomain(string $domain): TenantConfigResource
    {
        $tenant = $this->tenantRepository->findByDomain($domain);
        if (!$tenant) {
            abort(404);
        }
        return new TenantConfigResource($tenant);
    }

    protected function getModelClass(): string
    {
        return Tenant::class;
    }
}
