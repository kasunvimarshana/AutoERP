<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\CreateLeaveAllocationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\DeleteLeaveAllocationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\GetLeaveAllocationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\ListLeaveAllocationsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\UpdateLeaveAllocationServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListLeaveAllocationRequest;
use Modules\HR\Presentation\Http\Requests\UpsertLeaveAllocationRequest;
use Modules\HR\Presentation\Http\Resources\LeaveAllocationResource;

final class LeaveAllocationController extends Controller
{
    public function __construct(
        private readonly ListLeaveAllocationsServiceInterface $listService,
        private readonly GetLeaveAllocationServiceInterface $getService,
        private readonly CreateLeaveAllocationServiceInterface $createService,
        private readonly UpdateLeaveAllocationServiceInterface $updateService,
        private readonly DeleteLeaveAllocationServiceInterface $deleteService,
    ) {
    }

    public function index(ListLeaveAllocationRequest $request): JsonResponse
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
            'data' => LeaveAllocationResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|LeaveAllocationResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new LeaveAllocationResource($result->valueOrFail());
    }

    public function store(UpsertLeaveAllocationRequest $request): JsonResponse|LeaveAllocationResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new LeaveAllocationResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertLeaveAllocationRequest $request, int|string $id): JsonResponse|LeaveAllocationResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new LeaveAllocationResource($result->valueOrFail());
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