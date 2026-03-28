<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Infrastructure\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Infrastructure\Http\Requests\UpdateSupplierRequest;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierCollection;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierResource;
use OpenApi\Attributes as OA;

class SupplierController extends BaseController
{
    public function __construct(
        CreateSupplierServiceInterface $createService,
        protected UpdateSupplierServiceInterface $updateService,
        protected DeleteSupplierServiceInterface $deleteService
    ) {
        parent::__construct($createService, SupplierResource::class, SupplierData::class);
    }

    #[OA\Get(
        path: '/api/suppliers',
        summary: 'List suppliers',
        tags: ['Suppliers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'code',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status',   in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'draft'])),
            new OA\Parameter(name: 'type',     in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['manufacturer', 'distributor', 'retailer', 'other'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',     in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',     in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'name:asc')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of suppliers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/SupplierObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): SupplierCollection
    {
        $this->authorize('viewAny', Supplier::class);
        $filters = $request->only(['name', 'code', 'status', 'type', 'tenant_id']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');

        $suppliers = $this->service->list($filters, $perPage, $page, $sort);

        return new SupplierCollection($suppliers);
    }

    #[OA\Post(
        path: '/api/suppliers',
        summary: 'Create supplier',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'name', 'code'],
                properties: [
                    new OA\Property(property: 'tenant_id',      type: 'integer', example: 1),
                    new OA\Property(property: 'name',           type: 'string',  maxLength: 255, example: 'Acme Supplies Ltd'),
                    new OA\Property(property: 'code',           type: 'string',  maxLength: 100, example: 'SUP-001'),
                    new OA\Property(property: 'user_id',        type: 'integer', nullable: true, example: null),
                    new OA\Property(property: 'email',          type: 'string',  nullable: true, example: 'contact@acme.example.com'),
                    new OA\Property(property: 'phone',          type: 'string',  nullable: true, example: '+1-555-0100'),
                    new OA\Property(property: 'address',        type: 'object',  nullable: true),
                    new OA\Property(property: 'contact_person', type: 'object',  nullable: true),
                    new OA\Property(property: 'payment_terms',  type: 'string',  nullable: true, example: 'net30'),
                    new OA\Property(property: 'currency',       type: 'string',  nullable: true, example: 'USD'),
                    new OA\Property(property: 'tax_number',     type: 'string',  nullable: true, example: 'TAX-123456'),
                    new OA\Property(property: 'status',         type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft'], example: 'active'),
                    new OA\Property(property: 'type',           type: 'string',  nullable: true, enum: ['manufacturer', 'distributor', 'retailer', 'other'], example: 'manufacturer'),
                    new OA\Property(property: 'attributes',     type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',       type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Suppliers'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Supplier created',
                content: new OA\JsonContent(ref: '#/components/schemas/SupplierObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);
        $dto = SupplierData::fromArray($request->validated());
        $supplier = $this->service->execute($dto->toArray());

        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/suppliers/{id}',
        summary: 'Get supplier',
        tags: ['Suppliers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supplier details',
                content: new OA\JsonContent(ref: '#/components/schemas/SupplierObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): SupplierResource
    {
        $supplier = $this->service->find($id);
        if (! $supplier) {
            abort(404);
        }
        $this->authorize('view', $supplier);

        return new SupplierResource($supplier);
    }

    #[OA\Put(
        path: '/api/suppliers/{id}',
        summary: 'Update supplier',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name',           type: 'string',  maxLength: 255),
                    new OA\Property(property: 'code',           type: 'string',  maxLength: 100),
                    new OA\Property(property: 'user_id',        type: 'integer', nullable: true),
                    new OA\Property(property: 'email',          type: 'string',  nullable: true),
                    new OA\Property(property: 'phone',          type: 'string',  nullable: true),
                    new OA\Property(property: 'address',        type: 'object',  nullable: true),
                    new OA\Property(property: 'contact_person', type: 'object',  nullable: true),
                    new OA\Property(property: 'payment_terms',  type: 'string',  nullable: true),
                    new OA\Property(property: 'currency',       type: 'string',  nullable: true),
                    new OA\Property(property: 'tax_number',     type: 'string',  nullable: true),
                    new OA\Property(property: 'status',         type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft']),
                    new OA\Property(property: 'type',           type: 'string',  nullable: true, enum: ['manufacturer', 'distributor', 'retailer', 'other']),
                    new OA\Property(property: 'attributes',     type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',       type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Suppliers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated supplier',
                content: new OA\JsonContent(ref: '#/components/schemas/SupplierObject')),
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
    public function update(UpdateSupplierRequest $request, int $id): SupplierResource
    {
        $supplier = $this->service->find($id);
        if (! $supplier) {
            abort(404);
        }
        $this->authorize('update', $supplier);
        $validated = $request->validated();
        $validated['id']        = $id;
        $validated['tenant_id'] = $supplier->getTenantId();
        $dto = SupplierData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());

        return new SupplierResource($updated);
    }

    #[OA\Delete(
        path: '/api/suppliers/{id}',
        summary: 'Delete supplier',
        tags: ['Suppliers'],
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
        $supplier = $this->service->find($id);
        if (! $supplier) {
            abort(404);
        }
        $this->authorize('delete', $supplier);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Supplier deleted successfully']);
    }

    protected function getModelClass(): string
    {
        return Supplier::class;
    }
}
