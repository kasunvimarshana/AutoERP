<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Contracts\AttendanceServiceInterface;
use Modules\HR\Infrastructure\Http\Resources\AttendanceResource;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = (int) $request->get('tenant_id', 0);
        $startDate = (string) $request->get('start_date', date('Y-m-01'));
        $endDate   = (string) $request->get('end_date', date('Y-m-d'));
        $result = $this->service->findByTenantAndDateRange(
            $tenantId, $startDate, $endDate,
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(new AttendanceResource($this->service->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $record = $this->service->create($request->all());
        return response()->json(new AttendanceResource($record), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = $this->service->update($id, $request->all());
        return response()->json(new AttendanceResource($record));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $employeeId = (int) $request->get('employee_id', 0);
        $source     = (string) $request->get('source', 'manual');
        $deviceId   = $request->get('device_id');
        $record = $this->service->checkIn($employeeId, $source, $deviceId);
        return response()->json(new AttendanceResource($record), 201);
    }

    public function checkOut(int $id): JsonResponse
    {
        $record = $this->service->checkOut($id);
        return response()->json(new AttendanceResource($record));
    }

    public function biometricCheckIn(Request $request): JsonResponse
    {
        $biometricData = (string) $request->get('biometric_data', '');
        $driver        = (string) $request->get('driver', 'mock');
        $record = $this->service->checkInViaBiometric($biometricData, $driver);
        return response()->json(new AttendanceResource($record), 201);
    }

    public function byEmployee(Request $request, int $employeeId): JsonResponse
    {
        $result = $this->service->findByEmployee(
            $employeeId,
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }
}
