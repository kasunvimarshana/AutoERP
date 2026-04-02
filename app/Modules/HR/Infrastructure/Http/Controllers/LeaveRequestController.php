<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\ApproveLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CreateLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\DeleteLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CancelLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\RejectLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\UpdateLeaveRequestServiceInterface;
use Modules\HR\Application\DTOs\LeaveRequestData;
use Modules\HR\Application\DTOs\UpdateLeaveRequestData;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Infrastructure\Http\Requests\StoreLeaveRequestRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateLeaveRequestRequest;
use Modules\HR\Infrastructure\Http\Resources\LeaveRequestCollection;
use Modules\HR\Infrastructure\Http\Resources\LeaveRequestResource;
use OpenApi\Attributes as OA;

class LeaveRequestController extends AuthorizedController
{
    public function __construct(
        protected FindLeaveRequestServiceInterface $findService,
        protected CreateLeaveRequestServiceInterface $createService,
        protected UpdateLeaveRequestServiceInterface $updateService,
        protected DeleteLeaveRequestServiceInterface $deleteService,
        protected ApproveLeaveRequestServiceInterface $approveService,
        protected RejectLeaveRequestServiceInterface $rejectService,
        protected CancelLeaveRequestServiceInterface $cancelService,
    ) {}

    #[OA\Get(
        path: '/api/hr/leave-requests',
        summary: 'List leave requests',
        tags: ['HR - Leave Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'employee_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'leave_type',  in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page',    in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',        in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',        in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of leave requests'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): LeaveRequestCollection
    {
        $this->authorize('viewAny', LeaveRequest::class);
        $filters = $request->only(['employee_id', 'status', 'leave_type']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');
        $leaveRequests = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new LeaveRequestCollection($leaveRequests);
    }

    #[OA\Post(
        path: '/api/hr/leave-requests',
        summary: 'Create leave request',
        tags: ['HR - Leave Requests'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['tenant_id', 'employee_id', 'leave_type', 'start_date', 'end_date'],
            properties: [
                new OA\Property(property: 'tenant_id',   type: 'integer'),
                new OA\Property(property: 'employee_id', type: 'integer'),
                new OA\Property(property: 'leave_type',  type: 'string', enum: ['annual', 'sick', 'personal', 'maternity', 'paternity', 'unpaid', 'other']),
                new OA\Property(property: 'start_date',  type: 'string', format: 'date'),
                new OA\Property(property: 'end_date',    type: 'string', format: 'date'),
                new OA\Property(property: 'reason',      type: 'string', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Leave request created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $this->authorize('create', LeaveRequest::class);
        $dto          = LeaveRequestData::fromArray($request->validated());
        $leaveRequest = $this->createService->execute($dto->toArray());

        return (new LeaveRequestResource($leaveRequest))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/hr/leave-requests/{id}',
        summary: 'Get leave request',
        tags: ['HR - Leave Requests'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Leave request details'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(int $id): LeaveRequestResource
    {
        $leaveRequest = $this->findService->find($id);
        if (! $leaveRequest) {
            abort(404);
        }
        $this->authorize('view', $leaveRequest);

        return new LeaveRequestResource($leaveRequest);
    }

    #[OA\Put(
        path: '/api/hr/leave-requests/{id}',
        summary: 'Update leave request',
        tags: ['HR - Leave Requests'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'leave_type', type: 'string'),
            new OA\Property(property: 'start_date', type: 'string', format: 'date'),
            new OA\Property(property: 'end_date',   type: 'string', format: 'date'),
            new OA\Property(property: 'reason',     type: 'string', nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Updated leave request'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function update(UpdateLeaveRequestRequest $request, int $id): LeaveRequestResource
    {
        $leaveRequest = $this->findService->find($id);
        if (! $leaveRequest) {
            abort(404);
        }
        $this->authorize('update', $leaveRequest);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdateLeaveRequestData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new LeaveRequestResource($updated);
    }

    #[OA\Delete(
        path: '/api/hr/leave-requests/{id}',
        summary: 'Delete leave request',
        tags: ['HR - Leave Requests'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $leaveRequest = $this->findService->find($id);
        if (! $leaveRequest) {
            abort(404);
        }
        $this->authorize('delete', $leaveRequest);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Leave request deleted successfully']);
    }

    #[OA\Post(
        path: '/api/hr/leave-requests/{id}/approve',
        summary: 'Approve leave request',
        tags: ['HR - Leave Requests'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'approved_by', type: 'integer'),
            new OA\Property(property: 'notes',       type: 'string', nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Leave request approved'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function approve(Request $request, int $id): JsonResponse
    {
        $leaveRequest = $this->findService->find($id);
        if (! $leaveRequest) {
            abort(404);
        }
        $this->authorize('update', $leaveRequest);
        $this->approveService->execute([
            'id'          => $id,
            'approved_by' => $request->input('approved_by'),
            'notes'       => $request->input('notes'),
        ]);

        return response()->json(['message' => 'Leave request approved successfully']);
    }

    #[OA\Post(
        path: '/api/hr/leave-requests/{id}/reject',
        summary: 'Reject leave request',
        tags: ['HR - Leave Requests'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'rejected_by', type: 'integer'),
            new OA\Property(property: 'notes',       type: 'string', nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Leave request rejected'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function reject(Request $request, int $id): JsonResponse
    {
        $leaveRequest = $this->findService->find($id);
        if (! $leaveRequest) {
            abort(404);
        }
        $this->authorize('update', $leaveRequest);
        $this->rejectService->execute([
            'id'          => $id,
            'approved_by' => $request->input('rejected_by'),
            'notes'       => $request->input('notes'),
        ]);

        return response()->json(['message' => 'Leave request rejected successfully']);
    }

    #[OA\Get(
        path: '/api/hr/leave-requests/by-employee/{employeeId}',
        summary: 'Get leave requests by employee',
        tags: ['HR - Leave Requests'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Leave requests for the employee')],
    )]
    public function byEmployee(int $employeeId): JsonResponse
    {
        $this->authorize('viewAny', LeaveRequest::class);
        $items = $this->findService->getByEmployee($employeeId);

        return response()->json(['data' => LeaveRequestResource::collection(collect($items))]);
    }

    #[OA\Post(
        path: '/api/hr/leave-requests/{id}/cancel',
        summary: 'Cancel leave request',
        tags: ['HR - Leave Requests'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Leave request cancelled'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function cancel(int $id): JsonResponse
    {
        $leaveRequest = $this->findService->find($id);
        if (! $leaveRequest) {
            abort(404);
        }
        $this->authorize('update', $leaveRequest);
        $this->cancelService->execute(['id' => $id]);

        return response()->json(['message' => 'Leave request cancelled successfully']);
    }
}
