<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreateAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\FindAttendanceLogServiceInterface;
use Modules\HR\Domain\Entities\AttendanceLog;
use Modules\HR\Infrastructure\Http\Requests\StoreAttendanceLogRequest;
use Modules\HR\Infrastructure\Http\Resources\AttendanceLogResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendanceLogController extends AuthorizedController
{
    public function __construct(
        protected CreateAttendanceLogServiceInterface $createService,
        protected FindAttendanceLogServiceInterface $findService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AttendanceLog::class);
        $result = $this->findService->list();

        return Response::json(['data' => AttendanceLogResource::collection($result)]);
    }

    public function store(StoreAttendanceLogRequest $request): JsonResponse
    {
        $this->authorize('create', AttendanceLog::class);
        $entity = $this->createService->execute($request->validated());

        return (new AttendanceLogResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $attendanceLog): AttendanceLogResource
    {
        $entity = $this->findOrFail($attendanceLog);
        $this->authorize('view', $entity);

        return new AttendanceLogResource($entity);
    }

    private function findOrFail(int $id): AttendanceLog
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Attendance log not found.');
        }

        return $entity;
    }
}
