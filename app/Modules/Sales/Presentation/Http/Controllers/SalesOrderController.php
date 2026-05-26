<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\CreateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\DeleteSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\GetSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\ListSalesOrdersServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\UpdateSalesOrderServiceInterface;
use Modules\Sales\Presentation\Http\Requests\ListSalesOrderRequest;
use Modules\Sales\Presentation\Http\Requests\UpsertSalesOrderRequest;
use Modules\Sales\Presentation\Http\Resources\SalesOrderResource;
use Modules\Sales\Domain\Services\SalesLifecycleService;
use Throwable;

final class SalesOrderController extends Controller
{
    public function __construct(
        private readonly ListSalesOrdersServiceInterface $listService,
        private readonly GetSalesOrderServiceInterface $getService,
        private readonly CreateSalesOrderServiceInterface $createService,
        private readonly UpdateSalesOrderServiceInterface $updateService,
        private readonly DeleteSalesOrderServiceInterface $deleteService,
        private readonly SalesLifecycleService $lifecycle,
    ) {
    }

    public function index(ListSalesOrderRequest $request): JsonResponse
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
            'data' => SalesOrderResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SalesOrderResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SalesOrderResource($result->valueOrFail());
    }

    public function store(UpsertSalesOrderRequest $request): JsonResponse|SalesOrderResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SalesOrderResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSalesOrderRequest $request, int|string $id): JsonResponse|SalesOrderResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SALES_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SalesOrderResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    public function confirm(int|string $id): JsonResponse|SalesOrderResource
    {
        try {
            return new SalesOrderResource($this->lifecycle->confirmSalesOrder((int) $id));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
