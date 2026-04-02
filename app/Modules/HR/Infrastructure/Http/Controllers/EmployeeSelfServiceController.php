<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Contracts\CancelLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CreateLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\FindAttendanceServiceInterface;
use Modules\HR\Application\Contracts\FindEmployeeServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveRequestServiceInterface;
use Modules\HR\Application\DTOs\LeaveRequestData;
use Modules\HR\Infrastructure\Http\Requests\SelfServiceLeaveRequestRequest;
use Modules\HR\Infrastructure\Http\Resources\AttendanceCollection;
use Modules\HR\Infrastructure\Http\Resources\EmployeeResource;
use Modules\HR\Infrastructure\Http\Resources\LeaveRequestCollection;
use Modules\HR\Infrastructure\Http\Resources\LeaveRequestResource;
use OpenApi\Attributes as OA;

class EmployeeSelfServiceController extends Controller
{
    public function __construct(
        private readonly FindEmployeeServiceInterface $findEmployeeService,
        private readonly FindLeaveRequestServiceInterface $findLeaveRequestService,
        private readonly CreateLeaveRequestServiceInterface $createLeaveRequestService,
        private readonly CancelLeaveRequestServiceInterface $cancelLeaveRequestService,
        private readonly FindAttendanceServiceInterface $findAttendanceService,
    ) {}

    #[OA\Get(
        path: '/api/hr/me/profile',
        summary: 'Get authenticated employee profile',
        tags: ['HR - Self Service'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Employee profile for the authenticated user'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'No employee record linked to your account'),
        ],
    )]
    public function profile(Request $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $employee = $this->findEmployeeService->findByUserId($userId);
        if (! $employee) {
            abort(404, 'No employee record linked to your account.');
        }

        return (new EmployeeResource($employee))->response();
    }

    #[OA\Get(
        path: '/api/hr/me/leave-requests',
        summary: 'Get authenticated employee leave requests',
        tags: ['HR - Self Service'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Leave requests for the authenticated employee'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'No employee record linked to your account'),
        ],
    )]
    public function leaveRequests(Request $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $employee = $this->findEmployeeService->findByUserId($userId);
        if (! $employee) {
            abort(404, 'No employee record linked to your account.');
        }

        $leaves = $this->findLeaveRequestService->getByEmployee($employee->getId());

        return (new LeaveRequestCollection($leaves))->response();
    }

    #[OA\Post(
        path: '/api/hr/me/leave-requests',
        summary: 'Submit a leave request for the authenticated employee',
        tags: ['HR - Self Service'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['leave_type', 'start_date', 'end_date'],
            properties: [
                new OA\Property(property: 'leave_type', type: 'string', enum: ['annual', 'sick', 'personal', 'maternity', 'paternity', 'unpaid', 'other']),
                new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                new OA\Property(property: 'end_date',   type: 'string', format: 'date'),
                new OA\Property(property: 'reason',     type: 'string', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Leave request submitted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'No employee record linked to your account'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function submitLeaveRequest(SelfServiceLeaveRequestRequest $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $employee = $this->findEmployeeService->findByUserId($userId);
        if (! $employee) {
            abort(404, 'No employee record linked to your account.');
        }

        $data = array_merge($request->validated(), [
            'tenant_id'   => $employee->getTenantId(),
            'employee_id' => $employee->getId(),
        ]);

        $dto          = LeaveRequestData::fromArray($data);
        $leaveRequest = $this->createLeaveRequestService->execute($dto->toArray());

        return (new LeaveRequestResource($leaveRequest))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/api/hr/me/leave-requests/{id}/cancel',
        summary: 'Cancel one of the authenticated employee\'s leave requests',
        tags: ['HR - Self Service'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Leave request cancelled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found or not belonging to the authenticated employee'),
        ],
    )]
    public function cancelLeaveRequest(Request $request, int $id): JsonResponse
    {
        $userId       = $request->user()->id;
        $employee     = $this->findEmployeeService->findByUserId($userId);
        $leaveRequest = $this->findLeaveRequestService->find($id);

        if (! $employee || ! $leaveRequest || $leaveRequest->getEmployeeId() !== $employee->getId()) {
            abort(404, 'Leave request not found.');
        }

        $this->cancelLeaveRequestService->execute(['id' => $id]);

        return response()->json(['message' => 'Leave request cancelled successfully']);
    }

    #[OA\Get(
        path: '/api/hr/me/attendance',
        summary: 'Get authenticated employee attendance records',
        tags: ['HR - Self Service'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Attendance records for the authenticated employee'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'No employee record linked to your account'),
        ],
    )]
    public function attendance(Request $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $employee = $this->findEmployeeService->findByUserId($userId);
        if (! $employee) {
            abort(404, 'No employee record linked to your account.');
        }

        $records = $this->findAttendanceService->getByEmployee($employee->getId());

        return (new AttendanceCollection($records))->response();
    }
}
