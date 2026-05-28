<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\CreateAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\DeleteAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\GetAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\ListAttendanceLogsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\UpdateAttendanceLogServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListAttendanceLogRequest;
use Modules\HR\Presentation\Http\Requests\UpsertAttendanceLogRequest;
use Modules\HR\Presentation\Http\Resources\AttendanceLogResource;

final class AttendanceLogController extends Controller
{
    public function __construct(
        private readonly ListAttendanceLogsServiceInterface $listService,
        private readonly GetAttendanceLogServiceInterface $getService,
        private readonly CreateAttendanceLogServiceInterface $createService,
        private readonly UpdateAttendanceLogServiceInterface $updateService,
        private readonly DeleteAttendanceLogServiceInterface $deleteService,
    ) {
    }

    public function index(ListAttendanceLogRequest $request): JsonResponse
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
            'data' => AttendanceLogResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|AttendanceLogResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new AttendanceLogResource($result->valueOrFail());
    }

    public function store(UpsertAttendanceLogRequest $request): JsonResponse|AttendanceLogResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AttendanceLogResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertAttendanceLogRequest $request, int|string $id): JsonResponse|AttendanceLogResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new AttendanceLogResource($result->valueOrFail());
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
