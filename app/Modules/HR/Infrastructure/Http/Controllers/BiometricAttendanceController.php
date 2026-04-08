<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Biometric\BiometricAttendanceServiceInterface;
use Modules\HR\Application\Biometric\BiometricDeviceRegistryInterface;
use Modules\HR\Application\Biometric\BiometricEnrollmentServiceInterface;
use Modules\HR\Domain\Biometric\BiometricDeviceException;
use Modules\HR\Infrastructure\Http\Requests\BiometricCheckInRequest;
use Modules\HR\Infrastructure\Http\Requests\BiometricEnrollRequest;
use Modules\HR\Infrastructure\Http\Resources\AttendanceResource;
use OpenApi\Attributes as OA;

/**
 * Exposes HTTP endpoints for biometric-device-triggered attendance events
 * and employee biometric enrollment.
 *
 * The controller depends on abstract service interfaces only – swapping a
 * device SDK or enrollment strategy requires zero controller changes.
 */
class BiometricAttendanceController extends AuthorizedController
{
    public function __construct(
        protected BiometricAttendanceServiceInterface $attendanceService,
        protected BiometricEnrollmentServiceInterface $enrollmentService,
        protected BiometricDeviceRegistryInterface $deviceRegistry,
    ) {}

    #[OA\Post(
        path: '/api/hr/biometric/check-in',
        summary: 'Record a biometric check-in',
        tags: ['HR - Biometric'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['device_id', 'biometric_template'],
            properties: [
                new OA\Property(property: 'device_id',          type: 'string', description: 'ID of the scanning device'),
                new OA\Property(property: 'biometric_template', type: 'string', description: 'Base-64 encoded biometric template'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Attendance check-in recorded'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error or device / identity failure'),
        ],
    )]
    public function checkIn(BiometricCheckInRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-Id', 0);

        try {
            $attendance = $this->attendanceService->checkIn(
                deviceId:          $request->validated('device_id'),
                biometricTemplate: $request->validated('biometric_template'),
                tenantId:          $tenantId,
            );
        } catch (BiometricDeviceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new AttendanceResource($attendance))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/api/hr/biometric/check-out',
        summary: 'Record a biometric check-out',
        tags: ['HR - Biometric'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['device_id', 'biometric_template'],
            properties: [
                new OA\Property(property: 'device_id',          type: 'string', description: 'ID of the scanning device'),
                new OA\Property(property: 'biometric_template', type: 'string', description: 'Base-64 encoded biometric template'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Attendance check-out recorded'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error or device / identity failure'),
        ],
    )]
    public function checkOut(BiometricCheckInRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-Id', 0);

        try {
            $attendance = $this->attendanceService->checkOut(
                deviceId:          $request->validated('device_id'),
                biometricTemplate: $request->validated('biometric_template'),
                tenantId:          $tenantId,
            );
        } catch (BiometricDeviceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new AttendanceResource($attendance))->response();
    }

    #[OA\Post(
        path: '/api/hr/employees/{id}/biometric/enroll',
        summary: 'Enroll a biometric template for an employee',
        tags: ['HR - Biometric'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['device_id', 'biometric_template'],
            properties: [
                new OA\Property(property: 'device_id',          type: 'string', description: 'ID of the target device'),
                new OA\Property(property: 'biometric_template', type: 'string', description: 'Base-64 encoded biometric template'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Enrollment successful'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Enrollment failure'),
        ],
    )]
    public function enroll(BiometricEnrollRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->enrollmentService->enroll(
                employeeId:        $id,
                deviceId:          $request->validated('device_id'),
                biometricTemplate: $request->validated('biometric_template'),
            );
        } catch (BiometricDeviceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'     => $result ? 'Biometric template enrolled successfully.' : 'Enrollment failed.',
            'employee_id' => $id,
            'enrolled'    => $result,
        ]);
    }

    #[OA\Get(
        path: '/api/hr/biometric/devices',
        summary: 'List all registered biometric devices',
        tags: ['HR - Biometric'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of registered biometric devices'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function devices(): JsonResponse
    {
        $devices = array_map(
            fn ($device) => [
                'device_id'   => $device->getDeviceId(),
                'type'        => $device->getType(),
                'available'   => $device->isAvailable(),
            ],
            $this->deviceRegistry->all()
        );

        return response()->json(['data' => array_values($devices)]);
    }
}
