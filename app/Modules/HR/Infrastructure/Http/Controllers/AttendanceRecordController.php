<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\FindAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\ProcessAttendanceServiceInterface;
use Modules\HR\Application\Contracts\UpdateAttendanceRecordServiceInterface;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Infrastructure\Http\Requests\ProcessAttendanceRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateAttendanceRecordRequest;
use Modules\HR\Infrastructure\Http\Resources\AttendanceRecordResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendanceRecordController extends AuthorizedController
{
    public function __construct(
        protected FindAttendanceRecordServiceInterface $findService,
        protected UpdateAttendanceRecordServiceInterface $updateService,
        protected ProcessAttendanceServiceInterface $processService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->findService->list();

        return Response::json(['data' => AttendanceRecordResource::collection($result)]);
    }

    public function show(int $attendanceRecord): AttendanceRecordResource
    {
        return new AttendanceRecordResource($this->findOrFail($attendanceRecord));
    }

    public function update(UpdateAttendanceRecordRequest $request, int $attendanceRecord): AttendanceRecordResource
    {
        $this->findOrFail($attendanceRecord);
        $payload = $request->validated();
        $payload['id'] = $attendanceRecord;
        $updated = $this->updateService->execute($payload);

        return new AttendanceRecordResource($updated);
    }

    public function process(ProcessAttendanceRequest $request): JsonResponse
    {
        $this->processService->execute($request->validated());

        return Response::json(['message' => 'Attendance processed successfully.']);
    }

    private function findOrFail(int $id): AttendanceRecord
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Attendance record not found.');
        }

        return $entity;
    }
}
