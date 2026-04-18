<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateAccountServiceInterface;
use Modules\Finance\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Finance\Application\Contracts\FindAccountServiceInterface;
use Modules\Finance\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Finance\Domain\Entities\Account;
use Modules\Finance\Infrastructure\Http\Requests\ListAccountRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreAccountRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateAccountRequest;
use Modules\Finance\Infrastructure\Http\Resources\AccountCollection;
use Modules\Finance\Infrastructure\Http\Resources\AccountResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AccountController extends AuthorizedController
{
    public function __construct(
        private readonly CreateAccountServiceInterface $createAccountService,
        private readonly UpdateAccountServiceInterface $updateAccountService,
        private readonly DeleteAccountServiceInterface $deleteAccountService,
        private readonly FindAccountServiceInterface $findAccountService,
    ) {}

    public function index(ListAccountRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'code' => $validated['code'] ?? null,
            'name' => $validated['name'] ?? null,
            'type' => $validated['type'] ?? null,
            'sub_type' => $validated['sub_type'] ?? null,
            'normal_balance' => $validated['normal_balance'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $accounts = $this->findAccountService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new AccountCollection($accounts))->response();
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $this->authorize('create', Account::class);

        $account = $this->createAccountService->execute($request->validated());

        return (new AccountResource($account))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $account): AccountResource
    {
        $foundAccount = $this->findAccountOrFail($account);
        $this->authorize('view', $foundAccount);

        return new AccountResource($foundAccount);
    }

    public function update(UpdateAccountRequest $request, int $account): AccountResource
    {
        $foundAccount = $this->findAccountOrFail($account);
        $this->authorize('update', $foundAccount);

        $payload = $request->validated();
        $payload['id'] = $account;

        return new AccountResource($this->updateAccountService->execute($payload));
    }

    public function destroy(int $account): JsonResponse
    {
        $foundAccount = $this->findAccountOrFail($account);
        $this->authorize('delete', $foundAccount);

        $this->deleteAccountService->execute(['id' => $account]);

        return Response::json(['message' => 'Account deleted successfully']);
    }

    private function findAccountOrFail(int $accountId): Account
    {
        $account = $this->findAccountService->find($accountId);

        if (! $account) {
            throw new NotFoundHttpException('Account not found.');
        }

        return $account;
    }
}
