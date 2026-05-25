<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\CreateArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\DeleteArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\GetArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\ListArTransactionsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\UpdateArTransactionServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListArTransactionRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertArTransactionRequest;
use Modules\Finance\Presentation\Http\Resources\ArTransactionResource;

final class ArTransactionController extends Controller
{
    public function __construct(
        private readonly ListArTransactionsServiceInterface $listService,
        private readonly GetArTransactionServiceInterface $getService,
        private readonly CreateArTransactionServiceInterface $createService,
        private readonly UpdateArTransactionServiceInterface $updateService,
        private readonly DeleteArTransactionServiceInterface $deleteService,
    ) {
    }

    public function index(ListArTransactionRequest $request): JsonResponse
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

        if (array_key_exists('account_id', $validated) && $validated['account_id'] !== null) {
            $criteria['account_id'] = $validated['account_id'];
        }

        if (array_key_exists('transaction_type', $validated) && $validated['transaction_type'] !== null) {
            $criteria['transaction_type'] = $validated['transaction_type'];
        }

        if (array_key_exists('is_reconciled', $validated) && $validated['is_reconciled'] !== null) {
            $criteria['is_reconciled'] = $validated['is_reconciled'];
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
            'data' => ArTransactionResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ArTransactionResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ArTransactionResource($result->valueOrFail());
    }

    public function store(UpsertArTransactionRequest $request): JsonResponse|ArTransactionResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ArTransactionResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertArTransactionRequest $request, int|string $id): JsonResponse|ArTransactionResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ArTransactionResource($result->valueOrFail());
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
