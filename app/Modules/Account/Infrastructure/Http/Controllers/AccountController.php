<?php

declare(strict_types=1);

namespace Modules\Account\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Account\Application\Contracts\CreateAccountServiceInterface;
use Modules\Account\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Account\Application\Contracts\FindAccountServiceInterface;
use Modules\Account\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Account\Application\DTOs\AccountData;
use Modules\Account\Domain\Entities\Account;
use Modules\Account\Infrastructure\Http\Requests\StoreAccountRequest;
use Modules\Account\Infrastructure\Http\Requests\UpdateAccountRequest;
use Modules\Account\Infrastructure\Http\Resources\AccountCollection;
use Modules\Account\Infrastructure\Http\Resources\AccountResource;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use OpenApi\Attributes as OA;

class AccountController extends AuthorizedController
{
    public function __construct(
        protected FindAccountServiceInterface $findService,
        protected CreateAccountServiceInterface $createService,
        protected UpdateAccountServiceInterface $updateService,
        protected DeleteAccountServiceInterface $deleteService
    ) {}

    #[OA\Get(
        path: '/api/accounts',
        summary: 'List accounts',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'code',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'name',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type',      in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asset', 'liability', 'equity', 'income', 'expense'])),
            new OA\Parameter(name: 'subtype',   in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status',    in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'])),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',      in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'code:asc')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of accounts',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/AccountObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): AccountCollection
    {
        $this->authorize('viewAny', Account::class);
        $filters = $request->only(['code', 'name', 'type', 'subtype', 'status', 'parent_id']);
        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);
        $sort = $request->input('sort');

        $accounts = $this->findService->list($filters, $perPage, $page, $sort);

        return new AccountCollection($accounts);
    }

    #[OA\Post(
        path: '/api/accounts',
        summary: 'Create account',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'code', 'name', 'type'],
                properties: [
                    new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
                    new OA\Property(property: 'code',        type: 'string',  maxLength: 50,  example: '1000'),
                    new OA\Property(property: 'name',        type: 'string',  maxLength: 255, example: 'Cash'),
                    new OA\Property(property: 'type',        type: 'string',  enum: ['asset', 'liability', 'equity', 'income', 'expense'], example: 'asset'),
                    new OA\Property(property: 'subtype',     type: 'string',  nullable: true, maxLength: 100, example: 'current_asset'),
                    new OA\Property(property: 'description', type: 'string',  nullable: true, example: 'Cash on hand and in bank'),
                    new OA\Property(property: 'currency',    type: 'string',  nullable: true, example: 'USD'),
                    new OA\Property(property: 'balance',     type: 'number',  nullable: true, format: 'float', example: 0.00),
                    new OA\Property(property: 'is_system',   type: 'boolean', nullable: true, example: false),
                    new OA\Property(property: 'parent_id',   type: 'integer', nullable: true, example: null),
                    new OA\Property(property: 'status',      type: 'string',  nullable: true, enum: ['active', 'inactive'], example: 'active'),
                    new OA\Property(property: 'attributes',  type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Account created',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $this->authorize('create', Account::class);
        $dto = AccountData::fromArray($request->validated());
        $account = $this->createService->execute($dto->toArray());

        return (new AccountResource($account))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/accounts/{id}',
        summary: 'Get account',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Account details',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): AccountResource
    {
        $account = $this->findService->find($id);
        if (! $account) {
            abort(404);
        }
        $this->authorize('view', $account);

        return new AccountResource($account);
    }

    #[OA\Put(
        path: '/api/accounts/{id}',
        summary: 'Update account',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'name', 'type'],
                properties: [
                    new OA\Property(property: 'code',        type: 'string',  maxLength: 50),
                    new OA\Property(property: 'name',        type: 'string',  maxLength: 255),
                    new OA\Property(property: 'type',        type: 'string',  enum: ['asset', 'liability', 'equity', 'income', 'expense']),
                    new OA\Property(property: 'subtype',     type: 'string',  nullable: true, maxLength: 100),
                    new OA\Property(property: 'description', type: 'string',  nullable: true),
                    new OA\Property(property: 'currency',    type: 'string',  nullable: true),
                    new OA\Property(property: 'parent_id',   type: 'integer', nullable: true),
                    new OA\Property(property: 'status',      type: 'string',  nullable: true, enum: ['active', 'inactive']),
                    new OA\Property(property: 'attributes',  type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated account',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountObject')),
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
    public function update(UpdateAccountRequest $request, int $id): AccountResource
    {
        $account = $this->findService->find($id);
        if (! $account) {
            abort(404);
        }
        $this->authorize('update', $account);
        $validated              = $request->validated();
        $validated['id']        = $id;
        $validated['tenant_id'] = $account->getTenantId();
        $dto                    = AccountData::fromArray($validated);
        $updated                = $this->updateService->execute($dto->toArray());

        return new AccountResource($updated);
    }

    #[OA\Delete(
        path: '/api/accounts/{id}',
        summary: 'Delete account',
        tags: ['Accounts'],
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
        $account = $this->findService->find($id);
        if (! $account) {
            abort(404);
        }
        $this->authorize('delete', $account);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Account deleted successfully']);
    }
}
