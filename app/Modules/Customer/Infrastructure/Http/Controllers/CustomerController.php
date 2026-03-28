<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Infrastructure\Http\Requests\StoreCustomerRequest;
use Modules\Customer\Infrastructure\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\Infrastructure\Http\Resources\CustomerCollection;
use Modules\Customer\Infrastructure\Http\Resources\CustomerResource;
use OpenApi\Attributes as OA;

class CustomerController extends BaseController
{
    public function __construct(
        CreateCustomerServiceInterface $createService,
        protected UpdateCustomerServiceInterface $updateService,
        protected DeleteCustomerServiceInterface $deleteService
    ) {
        parent::__construct($createService, CustomerResource::class, CustomerData::class);
    }

    #[OA\Get(
        path: '/api/customers',
        summary: 'List customers',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',         in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'code',         in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status',       in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'draft'])),
            new OA\Parameter(name: 'type',         in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['retail', 'wholesale', 'corporate', 'vip', 'other'])),
            new OA\Parameter(name: 'loyalty_tier', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['bronze', 'silver', 'gold', 'platinum'])),
            new OA\Parameter(name: 'per_page',     in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',         in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',         in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'name:asc')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of customers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/CustomerObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): CustomerCollection
    {
        $this->authorize('viewAny', Customer::class);
        $filters = $request->only(['name', 'code', 'status', 'type', 'loyalty_tier', 'tenant_id']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');

        $customers = $this->service->list($filters, $perPage, $page, $sort);

        return new CustomerCollection($customers);
    }

    #[OA\Post(
        path: '/api/customers',
        summary: 'Create customer',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'name', 'code'],
                properties: [
                    new OA\Property(property: 'tenant_id',        type: 'integer', example: 1),
                    new OA\Property(property: 'name',             type: 'string',  maxLength: 255, example: 'Jane Smith'),
                    new OA\Property(property: 'code',             type: 'string',  maxLength: 100, example: 'CUST-001'),
                    new OA\Property(property: 'user_id',          type: 'integer', nullable: true, example: null),
                    new OA\Property(property: 'email',            type: 'string',  nullable: true, example: 'jane@example.com'),
                    new OA\Property(property: 'phone',            type: 'string',  nullable: true, example: '+1-555-0100'),
                    new OA\Property(property: 'billing_address',  type: 'object',  nullable: true),
                    new OA\Property(property: 'shipping_address', type: 'object',  nullable: true),
                    new OA\Property(property: 'date_of_birth',    type: 'string',  nullable: true, example: '1990-01-15'),
                    new OA\Property(property: 'loyalty_tier',     type: 'string',  nullable: true, enum: ['bronze', 'silver', 'gold', 'platinum'], example: 'gold'),
                    new OA\Property(property: 'credit_limit',     type: 'number',  nullable: true, example: 5000.00),
                    new OA\Property(property: 'payment_terms',    type: 'string',  nullable: true, example: 'net30'),
                    new OA\Property(property: 'currency',         type: 'string',  nullable: true, example: 'USD'),
                    new OA\Property(property: 'tax_number',       type: 'string',  nullable: true, example: 'TAX-123456'),
                    new OA\Property(property: 'status',           type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft'], example: 'active'),
                    new OA\Property(property: 'type',             type: 'string',  nullable: true, enum: ['retail', 'wholesale', 'corporate', 'vip', 'other'], example: 'retail'),
                    new OA\Property(property: 'attributes',       type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',         type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Customer created',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomerObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);
        $dto      = CustomerData::fromArray($request->validated());
        $customer = $this->service->execute($dto->toArray());

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/customers/{id}',
        summary: 'Get customer',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Customer details',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomerObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): CustomerResource
    {
        $customer = $this->service->find($id);
        if (! $customer) {
            abort(404);
        }
        $this->authorize('view', $customer);

        return new CustomerResource($customer);
    }

    #[OA\Put(
        path: '/api/customers/{id}',
        summary: 'Update customer',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name',             type: 'string',  maxLength: 255),
                    new OA\Property(property: 'code',             type: 'string',  maxLength: 100),
                    new OA\Property(property: 'user_id',          type: 'integer', nullable: true),
                    new OA\Property(property: 'email',            type: 'string',  nullable: true),
                    new OA\Property(property: 'phone',            type: 'string',  nullable: true),
                    new OA\Property(property: 'billing_address',  type: 'object',  nullable: true),
                    new OA\Property(property: 'shipping_address', type: 'object',  nullable: true),
                    new OA\Property(property: 'date_of_birth',    type: 'string',  nullable: true),
                    new OA\Property(property: 'loyalty_tier',     type: 'string',  nullable: true, enum: ['bronze', 'silver', 'gold', 'platinum']),
                    new OA\Property(property: 'credit_limit',     type: 'number',  nullable: true),
                    new OA\Property(property: 'payment_terms',    type: 'string',  nullable: true),
                    new OA\Property(property: 'currency',         type: 'string',  nullable: true),
                    new OA\Property(property: 'tax_number',       type: 'string',  nullable: true),
                    new OA\Property(property: 'status',           type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft']),
                    new OA\Property(property: 'type',             type: 'string',  nullable: true, enum: ['retail', 'wholesale', 'corporate', 'vip', 'other']),
                    new OA\Property(property: 'attributes',       type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',         type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated customer',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomerObject')),
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
    public function update(UpdateCustomerRequest $request, int $id): CustomerResource
    {
        $customer = $this->service->find($id);
        if (! $customer) {
            abort(404);
        }
        $this->authorize('update', $customer);
        $validated              = $request->validated();
        $validated['id']        = $id;
        $validated['tenant_id'] = $customer->getTenantId();
        $dto                    = CustomerData::fromArray($validated);
        $updated                = $this->updateService->execute($dto->toArray());

        return new CustomerResource($updated);
    }

    #[OA\Delete(
        path: '/api/customers/{id}',
        summary: 'Delete customer',
        tags: ['Customers'],
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
        $customer = $this->service->find($id);
        if (! $customer) {
            abort(404);
        }
        $this->authorize('delete', $customer);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    protected function getModelClass(): string
    {
        return Customer::class;
    }
}
