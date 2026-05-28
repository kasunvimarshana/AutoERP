<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\CreateAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\DeleteAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\GetAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\ListAttendanceRecordsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\UpdateAttendanceRecordServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListAttendanceRecordRequest;
use Modules\HR\Presentation\Http\Requests\UpsertAttendanceRecordRequest;
use Modules\HR\Presentation\Http\Resources\AttendanceRecordResource;

final class AttendanceRecordController extends Controller
{
    public function __construct(
        private readonly ListAttendanceRecordsServiceInterface $listService,
        private readonly GetAttendanceRecordServiceInterface $getService,
        private readonly CreateAttendanceRecordServiceInterface $createService,
        private readonly UpdateAttendanceRecordServiceInterface $updateService,
        private readonly DeleteAttendanceRecordServiceInterface $deleteService,
    ) {
    }

    public function index(ListAttendanceRecordRequest $request): JsonResponse
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
            'data' => AttendanceRecordResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|AttendanceRecordResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new AttendanceRecordResource($result->valueOrFail());
    }

    public function store(UpsertAttendanceRecordRequest $request): JsonResponse|AttendanceRecordResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AttendanceRecordResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertAttendanceRecordRequest $request, int|string $id): JsonResponse|AttendanceRecordResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new AttendanceRecordResource($result->valueOrFail());
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
