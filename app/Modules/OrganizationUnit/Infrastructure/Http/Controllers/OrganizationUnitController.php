<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\ListOrganizationUnitRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\StoreOrganizationUnitRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UpdateOrganizationUnitRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitCollection;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitResource;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrganizationUnitController extends AuthorizedController
{
    /** @var array<string> */
    private const SUPPORTED_INCLUDES = [
        'attachments',
        'users',
        'roles',
        'permissions',
        'devices',
        'user_attachments',
    ];

    public function __construct(
        protected FileStorageServiceInterface $storage,
        protected CreateOrganizationUnitServiceInterface $createOrganizationUnitService,
        protected UpdateOrganizationUnitServiceInterface $updateOrganizationUnitService,
        protected DeleteOrganizationUnitServiceInterface $deleteOrganizationUnitService,
        protected FindOrganizationUnitServiceInterface $findOrganizationUnitService,
        protected FindOrganizationUnitAttachmentsServiceInterface $findOrganizationUnitAttachmentsService,
        protected FindUserServiceInterface $findUserService,
        protected UploadOrganizationUnitAttachmentServiceInterface $uploadAttachmentService,
    ) {}

    public function index(ListOrganizationUnitRequest $request): JsonResponse
    {
        $this->authorize('viewAny', OrganizationUnit::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'type_id' => $validated['type_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'manager_user_id' => $validated['manager_user_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'code' => $validated['code'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sort = $validated['sort'] ?? null;
        $include = $validated['include'] ?? null;

        $organizationUnits = $this->findOrganizationUnitService->list($filters, $perPage, $page, $sort, $include);

        $normalizedIncludes = $this->parseIncludes($include);
        if ($normalizedIncludes !== [] && $organizationUnits instanceof LengthAwarePaginator) {
            $organizationUnits->setCollection(
                $organizationUnits->getCollection()->map(
                    fn (OrganizationUnit $organizationUnit): OrganizationUnitResource => $this->buildOrganizationUnitResource($organizationUnit, $normalizedIncludes)
                )
            );
        }

        return (new OrganizationUnitCollection($organizationUnits, $this->storage))->response();
    }

    public function store(StoreOrganizationUnitRequest $request): JsonResponse
    {
        $this->authorize('create', OrganizationUnit::class);

        $validated = $request->validated();
        $avatarFile = $request->file('avatar_file');

        $organizationUnit = DB::transaction(function () use ($validated, $avatarFile): OrganizationUnit {
            $payload = $validated;
            unset($payload['avatar_file']);

            $dto = OrganizationUnitData::fromArray($payload);
            $savedOrganizationUnit = $this->createOrganizationUnitService->execute($dto->toArray());

            if ($avatarFile !== null && $savedOrganizationUnit->getId() !== null) {
                $this->uploadAttachmentService->execute([
                    'org_unit_id' => $savedOrganizationUnit->getId(),
                    'file' => [
                        'tmp_path' => $avatarFile->getPathname(),
                        'name' => $avatarFile->getClientOriginalName(),
                        'mime_type' => (string) $avatarFile->getMimeType(),
                        'size' => (int) $avatarFile->getSize(),
                    ],
                    'type' => 'avatar',
                ]);

                return $this->findOrganizationUnitService->find($savedOrganizationUnit->getId()) ?? $savedOrganizationUnit;
            }

            return $savedOrganizationUnit;
        });

        $resource = $this->buildOrganizationUnitResource($organizationUnit, $this->parseIncludes($request->query('include')));

        return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $organizationUnitId): OrganizationUnitResource
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('view', $organizationUnit);

        return $this->buildOrganizationUnitResource($organizationUnit, $this->parseIncludes($request->query('include')));
    }

    public function update(UpdateOrganizationUnitRequest $request, int $organizationUnitId): OrganizationUnitResource
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('update', $organizationUnit);

        $validated = $request->validated();
        $avatarFile = $request->file('avatar_file');

        $updatedOrganizationUnit = DB::transaction(function () use ($validated, $avatarFile, $organizationUnitId): OrganizationUnit {
            $payload = $validated;
            unset($payload['avatar_file']);
            $payload['id'] = $organizationUnitId;

            $savedOrganizationUnit = $this->updateOrganizationUnitService->execute($payload);

            if ($avatarFile !== null) {
                $this->uploadAttachmentService->execute([
                    'org_unit_id' => $organizationUnitId,
                    'file' => [
                        'tmp_path' => $avatarFile->getPathname(),
                        'name' => $avatarFile->getClientOriginalName(),
                        'mime_type' => (string) $avatarFile->getMimeType(),
                        'size' => (int) $avatarFile->getSize(),
                    ],
                    'type' => 'avatar',
                ]);

                return $this->findOrganizationUnitService->find($organizationUnitId) ?? $savedOrganizationUnit;
            }

            return $savedOrganizationUnit;
        });

        return $this->buildOrganizationUnitResource($updatedOrganizationUnit, $this->parseIncludes($request->query('include')));
    }

    public function destroy(int $organizationUnitId): JsonResponse
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('delete', $organizationUnit);

        $this->deleteOrganizationUnitService->execute(['id' => $organizationUnitId]);

        return Response::json(['message' => 'Organization unit deleted successfully']);
    }

    private function findOrganizationUnitOrFail(int $organizationUnitId): OrganizationUnit
    {
        $organizationUnit = $this->findOrganizationUnitService->find($organizationUnitId);
        if (! $organizationUnit) {
            throw new NotFoundHttpException('Organization unit not found.');
        }

        return $organizationUnit;
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

    /**
     * @param  array<int, string>  $includes
     */
    private function buildOrganizationUnitResource(OrganizationUnit $organizationUnit, array $includes): OrganizationUnitResource
    {
        $organizationUnitId = $organizationUnit->getId();

        $includeUsers = $organizationUnitId !== null && (
            in_array('users', $includes, true)
            || in_array('roles', $includes, true)
            || in_array('permissions', $includes, true)
            || in_array('devices', $includes, true)
            || in_array('user_attachments', $includes, true)
        );

        return new OrganizationUnitResource(
            resource: $organizationUnit,
            storage: $this->storage,
            attachments: in_array('attachments', $includes, true) && $organizationUnitId !== null
                ? $this->findOrganizationUnitAttachmentsService->getByOrganizationUnit($organizationUnitId)
                : null,
            users: $includeUsers
                ? $this->collectOrganizationUnitUsers(
                    organizationUnitId: $organizationUnitId,
                    includeRoles: in_array('roles', $includes, true),
                    includePermissions: in_array('permissions', $includes, true),
                    includeDevices: in_array('devices', $includes, true),
                    includeUserAttachments: in_array('user_attachments', $includes, true),
                )
                : null,
        );
    }

    private function collectOrganizationUnitUsers(
        ?int $organizationUnitId,
        bool $includeRoles,
        bool $includePermissions,
        bool $includeDevices,
        bool $includeUserAttachments,
    ): ?Collection {
        if ($organizationUnitId === null) {
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

        $users = $this->findUserService->list(
            filters: ['org_unit_id' => $organizationUnitId],
            perPage: 100,
            page: 1,
            sort: null,
            include: empty($includeParts) ? null : implode(',', $includeParts),
        );

        return Collection::make($users->items());
    }
}
