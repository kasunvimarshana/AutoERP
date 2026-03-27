<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Brand\Application\Contracts\CreateBrandServiceInterface;
use Modules\Brand\Application\Contracts\DeleteBrandServiceInterface;
use Modules\Brand\Application\Contracts\UpdateBrandServiceInterface;
use Modules\Brand\Application\DTOs\BrandData;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Infrastructure\Http\Requests\StoreBrandRequest;
use Modules\Brand\Infrastructure\Http\Requests\UpdateBrandRequest;
use Modules\Brand\Infrastructure\Http\Resources\BrandCollection;
use Modules\Brand\Infrastructure\Http\Resources\BrandResource;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use OpenApi\Attributes as OA;

class BrandController extends BaseController
{
    public function __construct(
        CreateBrandServiceInterface $createService,
        protected UpdateBrandServiceInterface $updateService,
        protected DeleteBrandServiceInterface $deleteService
    ) {
        parent::__construct($createService, BrandResource::class, BrandData::class);
    }

    #[OA\Get(
        path: '/api/brands',
        summary: 'List brands',
        tags: ['Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'slug',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status',   in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'draft'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',     in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',     in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'name:asc')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of brands',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/BrandObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): BrandCollection
    {
        $this->authorize('viewAny', Brand::class);
        $filters = $request->only(['name', 'slug', 'status']);
        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);
        $sort = $request->input('sort');

        $brands = $this->service->list($filters, $perPage, $page, $sort);

        return new BrandCollection($brands);
    }

    #[OA\Post(
        path: '/api/brands',
        summary: 'Create brand',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'name'],
                properties: [
                    new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
                    new OA\Property(property: 'name',        type: 'string',  maxLength: 255, example: 'Acme Brand'),
                    new OA\Property(property: 'slug',        type: 'string',  nullable: true, maxLength: 255, example: 'acme-brand'),
                    new OA\Property(property: 'description', type: 'string',  nullable: true, example: 'A well-known brand'),
                    new OA\Property(property: 'website',     type: 'string',  nullable: true, example: 'https://acme.example.com'),
                    new OA\Property(property: 'status',      type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft'], example: 'active'),
                    new OA\Property(property: 'attributes',  type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Brands'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Brand created',
                content: new OA\JsonContent(ref: '#/components/schemas/BrandObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $this->authorize('create', Brand::class);
        $validated = $request->validated();
        if (empty($validated['slug'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }
        $dto = BrandData::fromArray($validated);
        $brand = $this->service->execute($dto->toArray());

        return (new BrandResource($brand))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/brands/{id}',
        summary: 'Get brand',
        tags: ['Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Brand details',
                content: new OA\JsonContent(ref: '#/components/schemas/BrandObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): BrandResource
    {
        $brand = $this->service->find($id);
        if (! $brand) {
            abort(404);
        }
        $this->authorize('view', $brand);

        return new BrandResource($brand);
    }

    #[OA\Put(
        path: '/api/brands/{id}',
        summary: 'Update brand',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',        type: 'string',  maxLength: 255),
                    new OA\Property(property: 'slug',        type: 'string',  nullable: true, maxLength: 255),
                    new OA\Property(property: 'description', type: 'string',  nullable: true),
                    new OA\Property(property: 'website',     type: 'string',  nullable: true),
                    new OA\Property(property: 'status',      type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft']),
                    new OA\Property(property: 'attributes',  type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated brand',
                content: new OA\JsonContent(ref: '#/components/schemas/BrandObject')),
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
    public function update(UpdateBrandRequest $request, int $id): BrandResource
    {
        $brand = $this->service->find($id);
        if (! $brand) {
            abort(404);
        }
        $this->authorize('update', $brand);
        $validated = $request->validated();
        if (empty($validated['slug'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }
        $validated['id'] = $id;
        $validated['tenant_id'] = $brand->getTenantId();
        $dto = BrandData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());

        return new BrandResource($updated);
    }

    #[OA\Delete(
        path: '/api/brands/{id}',
        summary: 'Delete brand',
        tags: ['Brands'],
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
        $brand = $this->service->find($id);
        if (! $brand) {
            abort(404);
        }
        $this->authorize('delete', $brand);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Brand deleted successfully']);
    }

    protected function getModelClass(): string
    {
        return Brand::class;
    }
}
