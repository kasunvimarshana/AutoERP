<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\DTOs\TenantConfigData;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantConfigRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantConfigResource;
use Modules\Tenant\Infrastructure\Http\Resources\TenantResource;
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: '/api/tenants',
        summary: 'List tenants',
        tags: ['Tenants'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'domain',   in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'active',   in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',     in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include',  in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of tenants',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/TenantObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): TenantCollection
    {
        $this->authorize('viewAny', Tenant::class);
        $filters = $request->only(['name', 'domain', 'active']);

        if ($request->has('active')) {
            $filters['active'] = $request->boolean('active');
        }

        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);
        $sort = $request->input('sort');
        $include = $request->input('include');

        $tenants = $this->service->list($filters, $perPage, $page, $sort, $include);

        return new TenantCollection($tenants);
    }

    #[OA\Post(
        path: '/api/tenants',
        summary: 'Create tenant',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'database_config'],
                properties: [
                    new OA\Property(property: 'name',   type: 'string',  maxLength: 255, example: 'Acme Corp'),
                    new OA\Property(property: 'domain', type: 'string',  nullable: true, example: 'acme.example.com'),
                    new OA\Property(
                        property: 'database_config',
                        type: 'object',
                        required: ['driver', 'host', 'port', 'database', 'username', 'password'],
                        properties: [
                            new OA\Property(property: 'driver',   type: 'string', enum: ['mysql', 'pgsql', 'sqlite'], example: 'mysql'),
                            new OA\Property(property: 'host',     type: 'string', example: '127.0.0.1'),
                            new OA\Property(property: 'port',     type: 'integer', example: 3306),
                            new OA\Property(property: 'database', type: 'string', example: 'tenant_db'),
                            new OA\Property(property: 'username', type: 'string', example: 'db_user'),
                            new OA\Property(property: 'password', type: 'string', example: 'secret'),
                        ],
                    ),
                    new OA\Property(property: 'mail_config',   type: 'object', nullable: true, example: ['host' => 'smtp.example.com']),
                    new OA\Property(property: 'cache_config',  type: 'object', nullable: true, example: ['driver' => 'redis']),
                    new OA\Property(property: 'queue_config',  type: 'object', nullable: true, example: ['driver' => 'database']),
                    new OA\Property(property: 'feature_flags', type: 'object', nullable: true, example: ['billing' => true]),
                    new OA\Property(property: 'api_keys',      type: 'object', nullable: true, example: []),
                    new OA\Property(property: 'active',        type: 'boolean', default: true),
                ],
            ),
        ),
        tags: ['Tenants'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Tenant created',
                content: new OA\JsonContent(ref: '#/components/schemas/TenantObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreTenantRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Tenant::class);
        $dto = TenantData::fromArray($request->validated());
        $tenant = $this->service->execute($dto->toArray());

        return (new TenantResource($tenant))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/tenants/{id}',
        summary: 'Get tenant',
        tags: ['Tenants'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tenant details',
                content: new OA\JsonContent(ref: '#/components/schemas/TenantObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): TenantResource
    {
        $tenant = $this->service->find($id);
        if (! $tenant) {
            abort(404);
        }
        $this->authorize('view', $tenant);

        return new TenantResource($tenant);
    }

    #[OA\Put(
        path: '/api/tenants/{id}',
        summary: 'Update tenant',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name',   type: 'string'),
                    new OA\Property(property: 'domain', type: 'string'),
                    new OA\Property(property: 'active', type: 'boolean'),
                ],
            ),
        ),
        tags: ['Tenants'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated tenant',
                content: new OA\JsonContent(ref: '#/components/schemas/TenantObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateTenantRequest $request, int $id): TenantResource
    {
        $tenant = $this->service->find($id);
        if (! $tenant) {
            abort(404);
        }
        $this->authorize('update', $tenant);
        $validated = $request->validated();
        $validated['id'] = $id;
        $dto = TenantData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());

        return new TenantResource($updated);
    }

    #[OA\Patch(
        path: '/api/tenants/{id}/config',
        summary: 'Update tenant configuration',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'database_config', ref: '#/components/schemas/DatabaseConfigObject'),
                    new OA\Property(property: 'feature_flags',   type: 'object', example: ['billing' => true]),
                    new OA\Property(property: 'api_keys',        type: 'object', example: '{}'),
                ],
            ),
        ),
        tags: ['Tenants'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated tenant config',
                content: new OA\JsonContent(ref: '#/components/schemas/TenantConfigObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function updateConfig(UpdateTenantConfigRequest $request, int $id): TenantConfigResource
    {
        $tenant = $this->service->find($id);
        if (! $tenant) {
            abort(404);
        }
        $this->authorize('updateConfig', $tenant);
        $validated = $request->validated();
        $validated['id'] = $id;
        $dto = TenantConfigData::fromArray($validated);
        $updated = $this->configService->execute($dto->toArray());

        return new TenantConfigResource($updated);
    }

    #[OA\Delete(
        path: '/api/tenants/{id}',
        summary: 'Delete tenant',
        tags: ['Tenants'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $tenant = $this->service->find($id);
        if (! $tenant) {
            abort(404);
        }
        $this->authorize('delete', $tenant);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Tenant deleted successfully']);
    }

    #[OA\Get(
        path: '/api/config/domain/{domain}',
        summary: 'Get tenant config by domain',
        description: 'Retrieve tenant configuration by domain name. This endpoint is public.',
        tags: ['Tenants'],
        parameters: [
            new OA\Parameter(name: 'domain', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'acme.example.com')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tenant config',
                content: new OA\JsonContent(ref: '#/components/schemas/TenantConfigObject')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function configByDomain(string $domain): TenantConfigResource
    {
        $tenant = $this->tenantRepository->findByDomain($domain);
        if (! $tenant) {
            abort(404);
        }

        return new TenantConfigResource($tenant);
    }

    protected function getModelClass(): string
    {
        return Tenant::class;
    }
}
