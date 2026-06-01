<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\CreateSalesOrderLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\DeleteSalesOrderLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\GetSalesOrderLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\ListSalesOrderLinesServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\UpdateSalesOrderLineServiceInterface;
use Modules\Sales\Presentation\Http\Requests\ListSalesOrderLineRequest;
use Modules\Sales\Presentation\Http\Requests\UpsertSalesOrderLineRequest;
use Modules\Sales\Presentation\Http\Resources\SalesOrderLineResource;

final class SalesOrderLineController extends Controller
{
    public function __construct(
        private readonly ListSalesOrderLinesServiceInterface $listService,
        private readonly GetSalesOrderLineServiceInterface $getService,
        private readonly CreateSalesOrderLineServiceInterface $createService,
        private readonly UpdateSalesOrderLineServiceInterface $updateService,
        private readonly DeleteSalesOrderLineServiceInterface $deleteService,
    ) {}

    public function index(ListSalesOrderLineRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => SalesOrderLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SalesOrderLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SalesOrderLineResource($result->valueOrFail());
    }

    public function store(UpsertSalesOrderLineRequest $request): JsonResponse|SalesOrderLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SalesOrderLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSalesOrderLineRequest $request, int|string $id): JsonResponse|SalesOrderLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SALES_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SalesOrderLineResource($result->valueOrFail());
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
