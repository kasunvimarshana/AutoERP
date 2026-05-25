<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\CreateGdnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\DeleteGdnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\GetGdnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\ListGdnLinesServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\UpdateGdnLineServiceInterface;
use Modules\Sales\Presentation\Http\Requests\ListGdnLineRequest;
use Modules\Sales\Presentation\Http\Requests\UpsertGdnLineRequest;
use Modules\Sales\Presentation\Http\Resources\GdnLineResource;

final class GdnLineController extends Controller
{
    public function __construct(
        private readonly ListGdnLinesServiceInterface $listService,
        private readonly GetGdnLineServiceInterface $getService,
        private readonly CreateGdnLineServiceInterface $createService,
        private readonly UpdateGdnLineServiceInterface $updateService,
        private readonly DeleteGdnLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListGdnLineRequest $request): JsonResponse
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
            'data' => GdnLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|GdnLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new GdnLineResource($result->valueOrFail());
    }

    public function store(UpsertGdnLineRequest $request): JsonResponse|GdnLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new GdnLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertGdnLineRequest $request, int|string $id): JsonResponse|GdnLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SALES_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new GdnLineResource($result->valueOrFail());
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