<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\CreateGdnHeaderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\DeleteGdnHeaderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\GetGdnHeaderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\ListGdnHeadersServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\UpdateGdnHeaderServiceInterface;
use Modules\Sales\Presentation\Http\Requests\ListGdnHeaderRequest;
use Modules\Sales\Presentation\Http\Requests\UpsertGdnHeaderRequest;
use Modules\Sales\Presentation\Http\Resources\GdnHeaderResource;
use Modules\Sales\Domain\Services\SalesLifecycleService;
use Throwable;

final class GdnHeaderController extends Controller
{
    public function __construct(
        private readonly ListGdnHeadersServiceInterface $listService,
        private readonly GetGdnHeaderServiceInterface $getService,
        private readonly CreateGdnHeaderServiceInterface $createService,
        private readonly UpdateGdnHeaderServiceInterface $updateService,
        private readonly DeleteGdnHeaderServiceInterface $deleteService,
        private readonly SalesLifecycleService $lifecycle,
    ) {
    }

    public function index(ListGdnHeaderRequest $request): JsonResponse
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
            'data' => GdnHeaderResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|GdnHeaderResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new GdnHeaderResource($result->valueOrFail());
    }

    public function store(UpsertGdnHeaderRequest $request): JsonResponse|GdnHeaderResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new GdnHeaderResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertGdnHeaderRequest $request, int|string $id): JsonResponse|GdnHeaderResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SALES_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new GdnHeaderResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    public function confirm(int|string $id): JsonResponse|GdnHeaderResource
    {
        try {
            return new GdnHeaderResource($this->lifecycle->confirmGdn((int) $id));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
