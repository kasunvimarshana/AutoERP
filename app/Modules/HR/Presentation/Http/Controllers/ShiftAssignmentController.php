<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\CreateShiftAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\DeleteShiftAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\GetShiftAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\ListShiftAssignmentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\UpdateShiftAssignmentServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListShiftAssignmentRequest;
use Modules\HR\Presentation\Http\Requests\UpsertShiftAssignmentRequest;
use Modules\HR\Presentation\Http\Resources\ShiftAssignmentResource;

final class ShiftAssignmentController extends Controller
{
    public function __construct(
        private readonly ListShiftAssignmentsServiceInterface $listService,
        private readonly GetShiftAssignmentServiceInterface $getService,
        private readonly CreateShiftAssignmentServiceInterface $createService,
        private readonly UpdateShiftAssignmentServiceInterface $updateService,
        private readonly DeleteShiftAssignmentServiceInterface $deleteService,
    ) {
    }

    public function index(ListShiftAssignmentRequest $request): JsonResponse
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
            'data' => ShiftAssignmentResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ShiftAssignmentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ShiftAssignmentResource($result->valueOrFail());
    }

    public function store(UpsertShiftAssignmentRequest $request): JsonResponse|ShiftAssignmentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ShiftAssignmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertShiftAssignmentRequest $request, int|string $id): JsonResponse|ShiftAssignmentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ShiftAssignmentResource($result->valueOrFail());
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
