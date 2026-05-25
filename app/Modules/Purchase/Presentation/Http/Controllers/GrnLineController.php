<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\CreateGrnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\DeleteGrnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\GetGrnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\ListGrnLinesServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\UpdateGrnLineServiceInterface;
use Modules\Purchase\Presentation\Http\Requests\ListGrnLineRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertGrnLineRequest;
use Modules\Purchase\Presentation\Http\Resources\GrnLineResource;

final class GrnLineController extends Controller
{
    public function __construct(
        private readonly ListGrnLinesServiceInterface $listService,
        private readonly GetGrnLineServiceInterface $getService,
        private readonly CreateGrnLineServiceInterface $createService,
        private readonly UpdateGrnLineServiceInterface $updateService,
        private readonly DeleteGrnLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListGrnLineRequest $request): JsonResponse
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
            'data' => GrnLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|GrnLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new GrnLineResource($result->valueOrFail());
    }

    public function store(UpsertGrnLineRequest $request): JsonResponse|GrnLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new GrnLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertGrnLineRequest $request, int|string $id): JsonResponse|GrnLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PURCHASE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new GrnLineResource($result->valueOrFail());
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