<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\CreateBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\DeleteBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\GetBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\ListBankTransactionsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\UpdateBankTransactionServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListBankTransactionRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertBankTransactionRequest;
use Modules\Finance\Presentation\Http\Resources\BankTransactionResource;

final class BankTransactionController extends Controller
{
    public function __construct(
        private readonly ListBankTransactionsServiceInterface $listService,
        private readonly GetBankTransactionServiceInterface $getService,
        private readonly CreateBankTransactionServiceInterface $createService,
        private readonly UpdateBankTransactionServiceInterface $updateService,
        private readonly DeleteBankTransactionServiceInterface $deleteService,
    ) {
    }

    public function index(ListBankTransactionRequest $request): JsonResponse
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

        if (array_key_exists('bank_account_id', $validated) && $validated['bank_account_id'] !== null) {
            $criteria['bank_account_id'] = $validated['bank_account_id'];
        }

        if (array_key_exists('status', $validated) && $validated['status'] !== null) {
            $criteria['status'] = $validated['status'];
        }

        if (array_key_exists('type', $validated) && $validated['type'] !== null) {
            $criteria['type'] = $validated['type'];
        }

        if (array_key_exists('matched_journal_entry_id', $validated) && $validated['matched_journal_entry_id'] !== null) {
            $criteria['matched_journal_entry_id'] = $validated['matched_journal_entry_id'];
        }

        if (array_key_exists('category_rule_id', $validated) && $validated['category_rule_id'] !== null) {
            $criteria['category_rule_id'] = $validated['category_rule_id'];
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
            'data' => BankTransactionResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|BankTransactionResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new BankTransactionResource($result->valueOrFail());
    }

    public function store(UpsertBankTransactionRequest $request): JsonResponse|BankTransactionResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new BankTransactionResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertBankTransactionRequest $request, int|string $id): JsonResponse|BankTransactionResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new BankTransactionResource($result->valueOrFail());
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
