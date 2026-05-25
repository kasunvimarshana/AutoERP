<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\CreateBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\DeleteBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\GetBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\ListBankReconciliationsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\UpdateBankReconciliationServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListBankReconciliationRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertBankReconciliationRequest;
use Modules\Finance\Presentation\Http\Resources\BankReconciliationResource;

final class BankReconciliationController extends Controller
{
    public function __construct(
        private readonly ListBankReconciliationsServiceInterface $listService,
        private readonly GetBankReconciliationServiceInterface $getService,
        private readonly CreateBankReconciliationServiceInterface $createService,
        private readonly UpdateBankReconciliationServiceInterface $updateService,
        private readonly DeleteBankReconciliationServiceInterface $deleteService,
    ) {
    }

    public function index(ListBankReconciliationRequest $request): JsonResponse
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

        if (array_key_exists('completed_by', $validated) && $validated['completed_by'] !== null) {
            $criteria['completed_by'] = $validated['completed_by'];
        }

        if (array_key_exists('approved_by', $validated) && $validated['approved_by'] !== null) {
            $criteria['approved_by'] = $validated['approved_by'];
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
            'data' => BankReconciliationResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|BankReconciliationResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new BankReconciliationResource($result->valueOrFail());
    }

    public function store(UpsertBankReconciliationRequest $request): JsonResponse|BankReconciliationResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new BankReconciliationResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertBankReconciliationRequest $request, int|string $id): JsonResponse|BankReconciliationResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new BankReconciliationResource($result->valueOrFail());
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
