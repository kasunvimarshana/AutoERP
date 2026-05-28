<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\CreateLeavePolicyLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\DeleteLeavePolicyLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\GetLeavePolicyLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\ListLeavePolicyLinesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\UpdateLeavePolicyLineServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListLeavePolicyLineRequest;
use Modules\HR\Presentation\Http\Requests\UpsertLeavePolicyLineRequest;
use Modules\HR\Presentation\Http\Resources\LeavePolicyLineResource;

final class LeavePolicyLineController extends Controller
{
    public function __construct(
        private readonly ListLeavePolicyLinesServiceInterface $listService,
        private readonly GetLeavePolicyLineServiceInterface $getService,
        private readonly CreateLeavePolicyLineServiceInterface $createService,
        private readonly UpdateLeavePolicyLineServiceInterface $updateService,
        private readonly DeleteLeavePolicyLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListLeavePolicyLineRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pagedResult = $result->valueOrFail();
        if (! $pagedResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => LeavePolicyLineResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|LeavePolicyLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new LeavePolicyLineResource($result->valueOrFail());
    }

    public function store(UpsertLeavePolicyLineRequest $request): JsonResponse|LeavePolicyLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new LeavePolicyLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertLeavePolicyLineRequest $request, int|string $id): JsonResponse|LeavePolicyLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new LeavePolicyLineResource($result->valueOrFail());
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
