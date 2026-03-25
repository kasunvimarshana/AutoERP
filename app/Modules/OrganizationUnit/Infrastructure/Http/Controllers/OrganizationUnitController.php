<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\MoveOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\MoveOrganizationUnitData;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\MoveOrganizationUnitRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\StoreOrganizationUnitRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UpdateOrganizationUnitRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitCollection;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitResource;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitTreeResource;
use OpenApi\Attributes as OA;

class OrganizationUnitController extends BaseController
{
    public function __construct(
        CreateOrganizationUnitServiceInterface $createService,
        protected UpdateOrganizationUnitServiceInterface $updateService,
        protected DeleteOrganizationUnitServiceInterface $deleteService,
        protected MoveOrganizationUnitServiceInterface $moveService,
        protected OrganizationUnitRepositoryInterface $orgUnitRepository
    ) {
        parent::__construct($createService, OrganizationUnitResource::class, OrganizationUnitData::class);
    }

    #[OA\Get(
        path: '/api/org-units',
        summary: 'List organization units',
        tags: ['Organization Units'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'code',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'parent_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include',   in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of organization units',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/OrganizationUnitObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): OrganizationUnitCollection
    {
        $this->authorize('viewAny', OrganizationUnit::class);
        $filters = $request->only(['name', 'code', 'parent_id']);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $sort = $request->input('sort');
        $include = $request->input('include');

        $units = $this->service->list($filters, $perPage, $page, $sort, $include);

        return new OrganizationUnitCollection($units);
    }

    #[OA\Post(
        path: '/api/org-units',
        summary: 'Create organization unit',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'name', 'code'],
                properties: [
                    new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
                    new OA\Property(property: 'name',        type: 'string',  example: 'Engineering'),
                    new OA\Property(property: 'code',        type: 'string',  example: 'ENG'),
                    new OA\Property(property: 'description', type: 'string',  nullable: true),
                    new OA\Property(property: 'parent_id',   type: 'integer', nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Organization Units'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Organization unit created',
                content: new OA\JsonContent(ref: '#/components/schemas/OrganizationUnitObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreOrganizationUnitRequest $request): OrganizationUnitResource
    {
        $this->authorize('create', OrganizationUnit::class);
        $dto = OrganizationUnitData::fromArray($request->validated());
        $unit = $this->service->execute($dto->toArray());

        return new OrganizationUnitResource($unit);
    }

    #[OA\Get(
        path: '/api/org-units/{id}',
        summary: 'Get organization unit',
        tags: ['Organization Units'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Organization unit details',
                content: new OA\JsonContent(ref: '#/components/schemas/OrganizationUnitObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): OrganizationUnitResource
    {
        $unit = $this->service->find($id);
        if (! $unit) {
            abort(404);
        }
        $this->authorize('view', $unit);

        return new OrganizationUnitResource($unit);
    }

    #[OA\Put(
        path: '/api/org-units/{id}',
        summary: 'Update organization unit',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name',        type: 'string'),
                    new OA\Property(property: 'code',        type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object', nullable: true),
                ],
            ),
        ),
        tags: ['Organization Units'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated organization unit',
                content: new OA\JsonContent(ref: '#/components/schemas/OrganizationUnitObject')),
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
    public function update(UpdateOrganizationUnitRequest $request, int $id): OrganizationUnitResource
    {
        $unit = $this->service->find($id);
        if (! $unit) {
            abort(404);
        }
        $this->authorize('update', $unit);
        $validated = $request->validated();
        $validated['id'] = $id;
        $dto = OrganizationUnitData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());

        return new OrganizationUnitResource($updated);
    }

    #[OA\Delete(
        path: '/api/org-units/{id}',
        summary: 'Delete organization unit',
        tags: ['Organization Units'],
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
        $unit = $this->service->find($id);
        if (! $unit) {
            abort(404);
        }
        $this->authorize('delete', $unit);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Organization unit deleted successfully']);
    }

    #[OA\Get(
        path: '/api/org-units/tree',
        summary: 'Get organization unit tree',
        description: 'Retrieve the full hierarchical tree of organization units.',
        tags: ['Organization Units'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'root_id', in: 'query', required: false, description: 'Optional root node ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hierarchical tree of organization units',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrganizationUnitObject')),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function tree(Request $request): OrganizationUnitTreeResource
    {
        $this->authorize('viewAny', OrganizationUnit::class);
        $tenantId = (int) tenant_id();
        $rootId = $request->input('root_id') !== null
            ? (int) $request->input('root_id')
            : null;
        $tree = $this->orgUnitRepository->getTree($tenantId, $rootId);

        return new OrganizationUnitTreeResource($tree);
    }

    #[OA\Patch(
        path: '/api/org-units/{id}/move',
        summary: 'Move organization unit',
        description: 'Move an organization unit to a new parent.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: 5),
                ],
            ),
        ),
        tags: ['Organization Units'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Moved',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
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
    public function move(MoveOrganizationUnitRequest $request, int $id): JsonResponse
    {
        $unit = $this->service->find($id);
        if (! $unit) {
            abort(404);
        }
        $this->authorize('move', $unit);
        $validated = $request->validated();
        $validated['id'] = $id;
        $dto = MoveOrganizationUnitData::fromArray($validated);
        $this->moveService->execute($dto->toArray());

        return response()->json(['message' => 'Organization unit moved successfully']);
    }

    protected function getModelClass(): string
    {
        return OrganizationUnit::class;
    }
}
