<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\CreateBudgetLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\DeleteBudgetLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\GetBudgetLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\ListBudgetLinesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\UpdateBudgetLineServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListBudgetLineRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertBudgetLineRequest;
use Modules\Finance\Presentation\Http\Resources\BudgetLineResource;

final class BudgetLineController extends Controller
{
    public function __construct(
        private readonly ListBudgetLinesServiceInterface $listService,
        private readonly GetBudgetLineServiceInterface $getService,
        private readonly CreateBudgetLineServiceInterface $createService,
        private readonly UpdateBudgetLineServiceInterface $updateService,
        private readonly DeleteBudgetLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListBudgetLineRequest $request): JsonResponse
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

        if (array_key_exists('budget_id', $validated) && $validated['budget_id'] !== null) {
            $criteria['budget_id'] = $validated['budget_id'];
        }

        if (array_key_exists('account_id', $validated) && $validated['account_id'] !== null) {
            $criteria['account_id'] = $validated['account_id'];
        }

        if (array_key_exists('cost_center_id', $validated) && $validated['cost_center_id'] !== null) {
            $criteria['cost_center_id'] = $validated['cost_center_id'];
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
            'data' => BudgetLineResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|BudgetLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new BudgetLineResource($result->valueOrFail());
    }

    public function store(UpsertBudgetLineRequest $request): JsonResponse|BudgetLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new BudgetLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertBudgetLineRequest $request, int|string $id): JsonResponse|BudgetLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new BudgetLineResource($result->valueOrFail());
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
