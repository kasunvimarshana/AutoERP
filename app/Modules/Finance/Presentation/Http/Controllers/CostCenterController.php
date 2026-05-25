<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\CreateCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\DeleteCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\GetCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\ListCostCentersServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\UpdateCostCenterServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListCostCenterRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertCostCenterRequest;
use Modules\Finance\Presentation\Http\Resources\CostCenterResource;

final class CostCenterController extends Controller
{
    public function __construct(
        private readonly ListCostCentersServiceInterface $listService,
        private readonly GetCostCenterServiceInterface $getService,
        private readonly CreateCostCenterServiceInterface $createService,
        private readonly UpdateCostCenterServiceInterface $updateService,
        private readonly DeleteCostCenterServiceInterface $deleteService,
    ) {
    }

    public function index(ListCostCenterRequest $request): JsonResponse
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
            'data' => CostCenterResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|CostCenterResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CostCenterResource($result->valueOrFail());
    }

    public function store(UpsertCostCenterRequest $request): JsonResponse|CostCenterResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CostCenterResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCostCenterRequest $request, int|string $id): JsonResponse|CostCenterResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CostCenterResource($result->valueOrFail());
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
