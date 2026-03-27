<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Category\Application\Contracts\CreateCategoryServiceInterface;
use Modules\Category\Application\Contracts\DeleteCategoryServiceInterface;
use Modules\Category\Application\Contracts\UpdateCategoryServiceInterface;
use Modules\Category\Application\DTOs\CategoryData;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Http\Requests\StoreCategoryRequest;
use Modules\Category\Infrastructure\Http\Requests\UpdateCategoryRequest;
use Modules\Category\Infrastructure\Http\Resources\CategoryCollection;
use Modules\Category\Infrastructure\Http\Resources\CategoryResource;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use OpenApi\Attributes as OA;

class CategoryController extends BaseController
{
    public function __construct(
        CreateCategoryServiceInterface $createService,
        protected UpdateCategoryServiceInterface $updateService,
        protected DeleteCategoryServiceInterface $deleteService,
        protected CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($createService, CategoryResource::class, CategoryData::class);
    }

    #[OA\Get(
        path: '/api/categories',
        summary: 'List categories',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'slug',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status',    in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'draft'])),
            new OA\Parameter(name: 'parent_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', nullable: true)),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',      in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'name:asc')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of categories',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/CategoryObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): CategoryCollection
    {
        $this->authorize('viewAny', Category::class);
        $filters = $request->only(['name', 'slug', 'status', 'parent_id']);
        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);
        $sort = $request->input('sort');

        $categories = $this->service->list($filters, $perPage, $page, $sort);

        return new CategoryCollection($categories);
    }

    #[OA\Post(
        path: '/api/categories',
        summary: 'Create category',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'name'],
                properties: [
                    new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
                    new OA\Property(property: 'name',        type: 'string',  maxLength: 255, example: 'Electronics'),
                    new OA\Property(property: 'slug',        type: 'string',  nullable: true, maxLength: 255, example: 'electronics'),
                    new OA\Property(property: 'description', type: 'string',  nullable: true, example: 'Electronic products'),
                    new OA\Property(property: 'parent_id',   type: 'integer', nullable: true, example: null),
                    new OA\Property(property: 'status',      type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft'], example: 'active'),
                    new OA\Property(property: 'attributes',  type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Category created',
                content: new OA\JsonContent(ref: '#/components/schemas/CategoryObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);
        $validated = $request->validated();
        if (empty($validated['slug'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }
        $dto = CategoryData::fromArray($validated);
        $category = $this->service->execute($dto->toArray());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/categories/{id}',
        summary: 'Get category',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category details',
                content: new OA\JsonContent(ref: '#/components/schemas/CategoryObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): CategoryResource
    {
        $category = $this->service->find($id);
        if (! $category) {
            abort(404);
        }
        $this->authorize('view', $category);

        return new CategoryResource($category);
    }

    #[OA\Put(
        path: '/api/categories/{id}',
        summary: 'Update category',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',        type: 'string',  maxLength: 255),
                    new OA\Property(property: 'slug',        type: 'string',  nullable: true, maxLength: 255),
                    new OA\Property(property: 'description', type: 'string',  nullable: true),
                    new OA\Property(property: 'parent_id',   type: 'integer', nullable: true),
                    new OA\Property(property: 'status',      type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft']),
                    new OA\Property(property: 'attributes',  type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category updated',
                content: new OA\JsonContent(ref: '#/components/schemas/CategoryObject')),
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
    public function update(UpdateCategoryRequest $request, int $id): CategoryResource
    {
        $category = $this->service->find($id);
        if (! $category) {
            abort(404);
        }
        $this->authorize('update', $category);
        $validated = $request->validated();
        $validated['id'] = $id;
        if (empty($validated['slug'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }

        $updated = $this->updateService->execute($validated);

        return new CategoryResource($updated);
    }

    #[OA\Delete(
        path: '/api/categories/{id}',
        summary: 'Delete category',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category deleted',
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
        $category = $this->service->find($id);
        if (! $category) {
            abort(404);
        }
        $this->authorize('delete', $category);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Category deleted successfully']);
    }

    #[OA\Get(
        path: '/api/categories/{id}/tree',
        summary: 'Get category tree',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id',        in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tenant_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category tree',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CategoryObject')),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function tree(Request $request, int $id): JsonResponse
    {
        $this->authorize('viewAny', Category::class);
        $tenantId = $request->integer('tenant_id', 1);
        $tree = $this->categoryRepository->getTree($tenantId, $id);

        return response()->json(['data' => CategoryResource::collection($tree)]);
    }

    #[OA\Get(
        path: '/api/categories/roots',
        summary: 'Get root categories for a tenant',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Root categories',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CategoryObject')),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function roots(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);
        $tenantId = $request->integer('tenant_id', 1);
        $roots = $this->categoryRepository->findRoots($tenantId);

        return response()->json(['data' => CategoryResource::collection($roots)]);
    }

    protected function getModelClass(): string
    {
        return Category::class;
    }
}
