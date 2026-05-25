<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\CreateBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\DeleteBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\GetBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\ListBankAccountsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\UpdateBankAccountServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListBankAccountRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertBankAccountRequest;
use Modules\Finance\Presentation\Http\Resources\BankAccountResource;

final class BankAccountController extends Controller
{
    public function __construct(
        private readonly ListBankAccountsServiceInterface $listService,
        private readonly GetBankAccountServiceInterface $getService,
        private readonly CreateBankAccountServiceInterface $createService,
        private readonly UpdateBankAccountServiceInterface $updateService,
        private readonly DeleteBankAccountServiceInterface $deleteService,
    ) {
    }

    public function index(ListBankAccountRequest $request): JsonResponse
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

        if (array_key_exists('currency_id', $validated) && $validated['currency_id'] !== null) {
            $criteria['currency_id'] = $validated['currency_id'];
        }

        if (array_key_exists('account_id', $validated) && $validated['account_id'] !== null) {
            $criteria['account_id'] = $validated['account_id'];
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
            'data' => BankAccountResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|BankAccountResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new BankAccountResource($result->valueOrFail());
    }

    public function store(UpsertBankAccountRequest $request): JsonResponse|BankAccountResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new BankAccountResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertBankAccountRequest $request, int|string $id): JsonResponse|BankAccountResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new BankAccountResource($result->valueOrFail());
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
