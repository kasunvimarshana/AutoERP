<?php

namespace Modules\HR\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\UseCases\CheckInUseCase;
use Modules\HR\Application\UseCases\CheckOutUseCase;
use Modules\HR\Infrastructure\Repositories\AttendanceRecordRepository;
use Modules\HR\Presentation\Requests\StoreCheckInRequest;
use Modules\HR\Presentation\Requests\StoreCheckOutRequest;
use Modules\Shared\Application\ResponseFormatter;

class AttendanceController extends Controller
{
    public function __construct(
        private CheckInUseCase               $checkInUseCase,
        private CheckOutUseCase              $checkOutUseCase,
        private AttendanceRecordRepository   $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function checkIn(StoreCheckInRequest $request): JsonResponse
    {
        $record = $this->checkInUseCase->execute($request->validated());
        return ResponseFormatter::success($record, 'Checked in successfully.', 201);
    }

    public function checkOut(StoreCheckOutRequest $request, string $id): JsonResponse
    {
        $data = array_merge($request->validated(), ['attendance_id' => $id]);
        $record = $this->checkOutUseCase->execute($data);
        return ResponseFormatter::success($record, 'Checked out successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $record = $this->repo->findById($id);
        if (! $record) {
            return ResponseFormatter::error('Attendance record not found.', [], 404);
        }
        return ResponseFormatter::success($record);
    }
}
