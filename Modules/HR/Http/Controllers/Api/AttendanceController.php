<?php

namespace Modules\HR\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Http\Requests\StoreAttendanceRequest;
use Modules\HR\Http\Requests\UpdateAttendanceRequest;
use Modules\HR\Http\Resources\AttendanceResource;
use Modules\HR\Services\AttendanceService;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            $attendances = $this->attendanceService->getByDateRange($startDate, $endDate);
        } else {
            $attendances = collect();
        }

        return AttendanceResource::collection($attendances)->response();
    }

    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $attendance = $this->attendanceService->create($request->validated());

        return (new AttendanceResource($attendance))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $attendance = $this->attendanceService->findById($id);
        if (! $attendance) {
            return response()->json(['message' => 'Attendance not found'], 404);
        }

        return (new AttendanceResource($attendance->load('employee')))->response();
    }

    public function update(UpdateAttendanceRequest $request, int $id): JsonResponse
    {
        $attendance = $this->attendanceService->update($id, $request->validated());

        return (new AttendanceResource($attendance))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->attendanceService->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Attendance not found'], 404);
        }

        return response()->json(['message' => 'Attendance deleted successfully'], 200);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $request->validate(['employee_id' => 'required|integer|exists:employees,id']);
        $attendance = $this->attendanceService->checkIn($request->input('employee_id'));

        return (new AttendanceResource($attendance))->response();
    }

    public function checkOut(Request $request): JsonResponse
    {
        $request->validate(['employee_id' => 'required|integer|exists:employees,id']);
        $attendance = $this->attendanceService->checkOut($request->input('employee_id'));

        return (new AttendanceResource($attendance))->response();
    }

    public function getByEmployee(int $employeeId, Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $attendances = $this->attendanceService->getByEmployee($employeeId, $startDate, $endDate);

        return AttendanceResource::collection($attendances)->response();
    }
}
