<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Application\DTOs\TenantConfigData;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantConfigRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantConfigResource;
use Modules\Tenant\Infrastructure\Http\Resources\TenantResource;
use OpenApi\Attributes as OA;

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

    
    public function index(Request $request): TenantCollection
    {
        $this->authorize('viewAny', Tenant::class);
        $filters = $request->only(['name', 'domain', 'active']);

        if ($request->has('active')) {
            $filters['active'] = $request->boolean('active');
        }

        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');

        $tenants = $this->findTenantService->list($filters, $perPage, $page, $sort, $include);

        return new TenantCollection($tenants);
    }

    
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);
        $dto    = TenantData::fromArray($request->validated());
        $tenant = $this->createService->execute($dto->toArray());

        if ($request->hasFile('logo')) {
            $this->uploadAttachmentService->execute([
                'tenant_id' => $tenant->getId(),
                'file'      => $request->file('logo'),
                'type'      => 'logo',
            ]);
            $tenant = $this->findTenantService->find($tenant->getId())
                ?? throw new \RuntimeException('Tenant disappeared after logo upload.');
        }

        return (new TenantResource($tenant))->response()->setStatusCode(201);
    }

    
    public function show(int $id): TenantResource
    {
        $tenant = $this->findTenantService->find($id);
        if (! $tenant) {
            abort(404);
        }
        $this->authorize('view', $tenant);

        return new TenantResource($tenant);
    }

    
    public function update(UpdateTenantRequest $request, int $id): TenantResource
    {
        $tenant = $this->findTenantService->find($id);
        if (! $tenant) {
            abort(404);
        }
        $this->authorize('update', $tenant);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = TenantData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        if ($request->hasFile('logo')) {
            $this->uploadAttachmentService->execute([
                'tenant_id' => $id,
                'file'      => $request->file('logo'),
                'type'      => 'logo',
            ]);
            $updated = $this->findTenantService->find($id)
                ?? throw new \RuntimeException('Tenant disappeared after logo upload.');
        }

        return new TenantResource($updated);
    }

    
    public function updateConfig(UpdateTenantConfigRequest $request, int $id): TenantConfigResource
    {
        $tenant = $this->findTenantService->find($id);
        if (! $tenant) {
            abort(404);
        }
        $this->authorize('updateConfig', $tenant);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = TenantConfigData::fromArray($validated);
        $updated         = $this->configService->execute($dto->toArray());

        return new TenantConfigResource($updated);
    }

    
    public function destroy(int $id): JsonResponse
    {
        $tenant = $this->findTenantService->find($id);
        if (! $tenant) {
            abort(404);
        }
        $this->authorize('delete', $tenant);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Tenant deleted successfully']);
    }

    
    public function configByDomain(string $domain): TenantConfigResource
    {
        $tenant = $this->findTenantService->findByDomain($domain);
        if (! $tenant) {
            abort(404);
        }

        return new TenantConfigResource($tenant);
    }


}
