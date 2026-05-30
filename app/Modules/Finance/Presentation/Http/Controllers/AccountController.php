<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\Accounts\CreateAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Accounts\DeleteAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Accounts\GetAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Accounts\ListAccountsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Accounts\UpdateAccountServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListAccountRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertAccountRequest;
use Modules\Finance\Presentation\Http\Resources\AccountResource;

final class AccountController extends Controller
{
    public function __construct(
        private readonly ListAccountsServiceInterface $listService,
        private readonly GetAccountServiceInterface $getService,
        private readonly CreateAccountServiceInterface $createService,
        private readonly UpdateAccountServiceInterface $updateService,
        private readonly DeleteAccountServiceInterface $deleteService,
    ) {}

    public function index(ListAccountRequest $request): JsonResponse
    {
        $criteria = [];
        $validated = $request->validated();

        if (isset($validated['tenant_id'])) {
            $criteria['tenant_id'] = (int) $validated['tenant_id'];
        }

        if (isset($validated['organization_unit_id'])) {
            $criteria['organization_unit_id'] = (int) $validated['organization_unit_id'];
        }

        if (isset($validated['search'])) {
            $search = trim((string) $validated['search']);
            if ($search !== '') {
                $criteria['search'] = $search;
            }
        }

        if (array_key_exists('parent_id', $validated) && $validated['parent_id'] !== null) {
            $criteria['parent_id'] = $validated['parent_id'];
        }

        if (array_key_exists('type', $validated) && $validated['type'] !== null) {
            $criteria['type'] = $validated['type'];
        }

        if (array_key_exists('normal_balance', $validated) && $validated['normal_balance'] !== null) {
            $criteria['normal_balance'] = $validated['normal_balance'];
        }

        if (array_key_exists('currency_id', $validated) && $validated['currency_id'] !== null) {
            $criteria['currency_id'] = $validated['currency_id'];
        }

        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
            $criteria['is_active'] = $validated['is_active'];
        }

        $result = $this->listService->execute(
            $criteria,
            (int) ($validated['per_page'] ?? 0),
            (int) ($validated['page'] ?? 0),
        );

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => AccountResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function tree(ListAccountRequest $request): JsonResponse
    {
        return $this->index($request);
    }

    public function show(int|string $id): JsonResponse|AccountResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new AccountResource($result->valueOrFail());
    }

    public function store(UpsertAccountRequest $request): JsonResponse|AccountResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AccountResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertAccountRequest $request, int|string $id): JsonResponse|AccountResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new AccountResource($result->valueOrFail());
    }

    public function activate(int|string $account): JsonResponse|AccountResource
    {
        $result = $this->updateService->execute($account, ['is_active' => true]);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new AccountResource($result->valueOrFail());
    }

    public function deactivate(int|string $account): JsonResponse|AccountResource
    {
        $result = $this->updateService->execute($account, ['is_active' => false]);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new AccountResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
