<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Accounting\Application\Commands\CreateAccountCommand;
use Modules\Accounting\Application\Commands\DeleteAccountCommand;
use Modules\Accounting\Application\Commands\UpdateAccountCommand;
use Modules\Accounting\Application\Services\AccountService;
use Modules\Accounting\Interfaces\Http\Requests\CreateAccountRequest;
use Modules\Accounting\Interfaces\Http\Requests\UpdateAccountRequest;
use Modules\Accounting\Interfaces\Http\Resources\AccountResource;

class AccountController extends BaseController
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->accountService->listAccounts($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($account) => (new AccountResource($account))->resolve(),
                $result['items']
            ),
            message: 'Accounts retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateAccountRequest $request): JsonResponse
    {
        try {
            $account = $this->accountService->createAccount(new CreateAccountCommand(
                tenantId: (int) $request->validated('tenant_id'),
                parentId: $request->validated('parent_id') ? (int) $request->validated('parent_id') : null,
                code: $request->validated('code'),
                name: $request->validated('name'),
                type: $request->validated('type'),
                description: $request->validated('description'),
                openingBalance: (string) $request->validated('opening_balance', '0.0000'),
            ));

            return $this->success(
                data: (new AccountResource($account))->resolve(),
                message: 'Account created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $account = $this->accountService->findAccountById($id, $tenantId);

        if ($account === null) {
            return $this->error('Account not found', status: 404);
        }

        return $this->success(
            data: (new AccountResource($account))->resolve(),
            message: 'Account retrieved successfully',
        );
    }

    public function update(UpdateAccountRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $account = $this->accountService->updateAccount(new UpdateAccountCommand(
                id: $id,
                tenantId: $tenantId,
                name: $request->validated('name'),
                description: $request->validated('description'),
                status: $request->validated('status'),
            ));

            return $this->success(
                data: (new AccountResource($account))->resolve(),
                message: 'Account updated successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->accountService->deleteAccount(new DeleteAccountCommand($id, $tenantId));

            return $this->success(message: 'Account deleted successfully');
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }
}
