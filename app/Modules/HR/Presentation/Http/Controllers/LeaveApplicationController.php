<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\CreateLeaveApplicationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\DeleteLeaveApplicationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\GetLeaveApplicationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\ListLeaveApplicationsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\UpdateLeaveApplicationServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListLeaveApplicationRequest;
use Modules\HR\Presentation\Http\Requests\UpsertLeaveApplicationRequest;
use Modules\HR\Presentation\Http\Resources\LeaveApplicationResource;

final class LeaveApplicationController extends Controller
{
    public function __construct(
        private readonly ListLeaveApplicationsServiceInterface $listService,
        private readonly GetLeaveApplicationServiceInterface $getService,
        private readonly CreateLeaveApplicationServiceInterface $createService,
        private readonly UpdateLeaveApplicationServiceInterface $updateService,
        private readonly DeleteLeaveApplicationServiceInterface $deleteService,
    ) {
    }

    public function index(ListLeaveApplicationRequest $request): JsonResponse
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
            'data' => LeaveApplicationResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|LeaveApplicationResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new LeaveApplicationResource($result->valueOrFail());
    }

    public function store(UpsertLeaveApplicationRequest $request): JsonResponse|LeaveApplicationResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new LeaveApplicationResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertLeaveApplicationRequest $request, int|string $id): JsonResponse|LeaveApplicationResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new LeaveApplicationResource($result->valueOrFail());
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
