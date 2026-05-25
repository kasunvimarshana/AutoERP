<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\Budgets\CreateBudgetServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Budgets\DeleteBudgetServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Budgets\GetBudgetServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Budgets\ListBudgetsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Budgets\UpdateBudgetServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListBudgetRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertBudgetRequest;
use Modules\Finance\Presentation\Http\Resources\BudgetResource;

final class BudgetController extends Controller
{
    public function __construct(
        private readonly ListBudgetsServiceInterface $listService,
        private readonly GetBudgetServiceInterface $getService,
        private readonly CreateBudgetServiceInterface $createService,
        private readonly UpdateBudgetServiceInterface $updateService,
        private readonly DeleteBudgetServiceInterface $deleteService,
    ) {
    }

    public function index(ListBudgetRequest $request): JsonResponse
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

        if (array_key_exists('fiscal_year_id', $validated) && $validated['fiscal_year_id'] !== null) {
            $criteria['fiscal_year_id'] = $validated['fiscal_year_id'];
        }

        if (array_key_exists('budget_type', $validated) && $validated['budget_type'] !== null) {
            $criteria['budget_type'] = $validated['budget_type'];
        }

        if (array_key_exists('status', $validated) && $validated['status'] !== null) {
            $criteria['status'] = $validated['status'];
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
            'data' => BudgetResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|BudgetResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new BudgetResource($result->valueOrFail());
    }

    public function store(UpsertBudgetRequest $request): JsonResponse|BudgetResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new BudgetResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertBudgetRequest $request, int|string $id): JsonResponse|BudgetResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new BudgetResource($result->valueOrFail());
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
