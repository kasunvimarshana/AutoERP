<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Infrastructure\Http\Requests\ListTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantConfigRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantConfigResource;
use Modules\Tenant\Infrastructure\Http\Resources\TenantResource;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantController extends AuthorizedController
{
    /** @var array<string> */
    private const SUPPORTED_INCLUDES = [
        'attachments',
        'users',
        'roles',
        'permissions',
        'devices',
        'user_attachments',
        'users.roles',
        'users.roles.permissions',
        'users.devices',
        'users.attachments',
    ];

    public function __construct(
        protected CreateTenantServiceInterface $createTenantService,
        protected UpdateTenantServiceInterface $updateTenantService,
        protected DeleteTenantServiceInterface $deleteTenantService,
        protected UpdateTenantConfigServiceInterface $updateTenantConfigService,
        protected FindTenantServiceInterface $findTenantService,
        protected FindTenantAttachmentsServiceInterface $findTenantAttachmentsService,
        protected FindUserServiceInterface $findUserService,
        protected UploadTenantAttachmentServiceInterface $uploadAttachmentService
    ) {}

    public function index(ListTenantRequest $request): JsonResponse
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
            $filters['active'] = (bool) $validated['active'];
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sort = $validated['sort'] ?? null;
        $include = $validated['include'] ?? null;

        $tenants = $this->findTenantService->list($filters, $perPage, $page, $sort, $include);

        $normalizedIncludes = $this->parseIncludes($include);
        if ($normalizedIncludes !== []) {
            $includeValue = implode(',', $normalizedIncludes);

            $tenants->setCollection(
                $tenants->getCollection()->map(
                    fn (Tenant $tenant): TenantResource => $this->buildTenantResource($tenant, $includeValue)
                )
            );
        }

        return (new TenantCollection($tenants))->response();
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);
        $validated = $request->validated();
        $logoFile = $request->file('logo');

        $tenant = DB::transaction(function () use ($validated, $logoFile): Tenant {
            $dto = TenantData::fromArray($validated);
            $createdTenant = $this->createTenantService->execute($dto->toArray());

            if ($logoFile !== null && $createdTenant->getId() !== null) {
                $this->uploadAttachmentService->execute([
                    'tenant_id' => $createdTenant->getId(),
                    'file' => $logoFile,
                    'type' => 'logo',
                ]);

                return $this->findTenantService->find($createdTenant->getId()) ?? $createdTenant;
            }

            return $createdTenant;
        });

        $resource = $this->buildTenantResource($tenant, $request->query('include'));

        return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $tenantId): TenantResource
    {
        $tenantEntity = $this->findTenantOrFail($tenantId);
        $this->authorize('view', $tenantEntity);

        return $this->buildTenantResource($tenantEntity, $request->query('include'));
    }

    public function update(UpdateTenantRequest $request, int $tenantId): TenantResource
    {
        $tenantEntity = $this->findTenantOrFail($tenantId);
        $this->authorize('update', $tenantEntity);

        $validated = $request->validated();
        $logoFile = $request->file('logo');

        $updated = DB::transaction(function () use ($validated, $logoFile, $tenantId): Tenant {
            $payload = $validated;
            $payload['id'] = $tenantId;
            $updatedTenant = $this->updateTenantService->execute($payload);

            if ($logoFile !== null) {
                $this->uploadAttachmentService->execute([
                    'tenant_id' => $tenantId,
                    'file' => $logoFile,
                    'type' => 'logo',
                ]);

                return $this->findTenantService->find($tenantId) ?? $updatedTenant;
            }

            return $updatedTenant;
        });

        return $this->buildTenantResource($updated, $request->query('include'));
    }

    public function updateConfig(UpdateTenantConfigRequest $request, int $tenantId): TenantConfigResource
    {
        $tenantEntity = $this->findTenantOrFail($tenantId);
        $this->authorize('updateConfig', $tenantEntity);

        $validated = $request->validated();
        $validated['id'] = $tenantId;
        $updated = $this->updateTenantConfigService->execute($validated);

        return new TenantConfigResource($updated);
    }

    public function destroy(int $tenantId): JsonResponse
    {
        $tenantEntity = $this->findTenantOrFail($tenantId);
        $this->authorize('delete', $tenantEntity);
        $this->deleteTenantService->execute(['id' => $tenantId]);

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

    private function buildTenantResource(Tenant $tenant, mixed $includeValue): TenantResource
    {
        $includes = $this->parseIncludes($includeValue);
        $tenantId = $tenant->getId();

        $includeUsers = $tenantId !== null && (
            in_array('users', $includes, true)
            || in_array('roles', $includes, true)
            || in_array('permissions', $includes, true)
            || in_array('devices', $includes, true)
            || in_array('user_attachments', $includes, true)
            || in_array('users.roles', $includes, true)
            || in_array('users.roles.permissions', $includes, true)
            || in_array('users.devices', $includes, true)
            || in_array('users.attachments', $includes, true)
        );

        return new TenantResource(
            resource: $tenant,
            attachments: in_array('attachments', $includes, true) && $tenantId !== null
                ? $this->findTenantAttachmentsService->findByTenant($tenantId)
                : null,
            users: $includeUsers
                ? $this->collectTenantUsers(
                    tenantId: $tenantId,
                    includeRoles: in_array('roles', $includes, true) || in_array('users.roles', $includes, true),
                    includePermissions: in_array('permissions', $includes, true) || in_array('users.roles.permissions', $includes, true),
                    includeDevices: in_array('devices', $includes, true) || in_array('users.devices', $includes, true),
                    includeUserAttachments: in_array('user_attachments', $includes, true) || in_array('users.attachments', $includes, true),
                )
                : null
        );
    }

    /**
     * @return array<int, string>
     */
    private function parseIncludes(mixed $includeValue): array
    {
        if (! is_string($includeValue) || trim($includeValue) === '') {
            return [];
        }

        $requestedIncludes = array_map('trim', explode(',', $includeValue));

        return array_values(array_unique(array_filter(
            $requestedIncludes,
            fn (string $include): bool => in_array($include, self::SUPPORTED_INCLUDES, true)
        )));
    }

    private function collectTenantUsers(
        ?int $tenantId,
        bool $includeRoles,
        bool $includePermissions,
        bool $includeDevices,
        bool $includeUserAttachments,
    ): ?Collection {
        if ($tenantId === null) {
            return null;
        }

        $includeParts = [];
        if ($includeRoles) {
            $includeParts[] = 'roles';
        }
        if ($includePermissions) {
            $includeParts[] = 'permissions';
        }
        if ($includeDevices) {
            $includeParts[] = 'devices';
        }
        if ($includeUserAttachments) {
            $includeParts[] = 'attachments';
        }

        $usersPaginator = $this->findUserService->list(
            filters: ['tenant_id' => $tenantId],
            perPage: 100,
            page: 1,
            sort: null,
            include: empty($includeParts) ? null : implode(',', $includeParts)
        );

        return Collection::make($usersPaginator->items());
    }
}
