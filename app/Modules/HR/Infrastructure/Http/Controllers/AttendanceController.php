<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreateAttendanceServiceInterface;
use Modules\HR\Application\Contracts\DeleteAttendanceServiceInterface;
use Modules\HR\Application\Contracts\FindAttendanceServiceInterface;
use Modules\HR\Application\Contracts\UpdateAttendanceServiceInterface;
use Modules\HR\Application\DTOs\AttendanceData;
use Modules\HR\Application\DTOs\UpdateAttendanceData;
use Modules\HR\Domain\Entities\Attendance;
use Modules\HR\Infrastructure\Http\Requests\StoreAttendanceRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateAttendanceRequest;
use Modules\HR\Infrastructure\Http\Resources\AttendanceCollection;
use Modules\HR\Infrastructure\Http\Resources\AttendanceResource;
use OpenApi\Attributes as OA;

class AttendanceController extends AuthorizedController
{
    public function __construct(
        protected FindAttendanceServiceInterface $findService,
        protected CreateAttendanceServiceInterface $createService,
        protected UpdateAttendanceServiceInterface $updateService,
        protected DeleteAttendanceServiceInterface $deleteService,
    ) {}

    #[OA\Get(
        path: '/api/hr/attendance',
        summary: 'List attendance records',
        tags: ['HR - Attendance'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'employee_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date',        in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page',    in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',        in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',        in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of attendance records'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): AttendanceCollection
    {
        $this->authorize('viewAny', Attendance::class);
        $filters = $request->only(['employee_id', 'status', 'date']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');
        $records = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new AttendanceCollection($records);
    }

    #[OA\Post(
        path: '/api/hr/attendance',
        summary: 'Create attendance record',
        tags: ['HR - Attendance'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['tenant_id', 'employee_id', 'date', 'check_in_time', 'status'],
            properties: [
                new OA\Property(property: 'tenant_id',      type: 'integer'),
                new OA\Property(property: 'employee_id',    type: 'integer'),
                new OA\Property(property: 'date',           type: 'string',  format: 'date'),
                new OA\Property(property: 'check_in_time',  type: 'string',  format: 'date-time'),
                new OA\Property(property: 'check_out_time', type: 'string',  format: 'date-time', nullable: true),
                new OA\Property(property: 'status',         type: 'string',  enum: ['present', 'absent', 'late', 'half_day', 'on_leave']),
                new OA\Property(property: 'hours_worked',   type: 'number',  nullable: true),
                new OA\Property(property: 'notes',          type: 'string',  nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Attendance record created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $this->authorize('create', Attendance::class);
        $dto        = AttendanceData::fromArray($request->validated());
        $attendance = $this->createService->execute($dto->toArray());

        return (new AttendanceResource($attendance))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/hr/attendance/{id}',
        summary: 'Get attendance record',
        tags: ['HR - Attendance'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Attendance record details'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(int $id): AttendanceResource
    {
        $attendance = $this->findService->find($id);
        if (! $attendance) {
            abort(404);
        }
        $this->authorize('view', $attendance);

        return new AttendanceResource($attendance);
    }

    #[OA\Put(
        path: '/api/hr/attendance/{id}',
        summary: 'Update attendance record',
        tags: ['HR - Attendance'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'date',           type: 'string', format: 'date'),
            new OA\Property(property: 'check_in_time',  type: 'string', format: 'date-time'),
            new OA\Property(property: 'check_out_time', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'status',         type: 'string'),
            new OA\Property(property: 'hours_worked',   type: 'number', nullable: true),
            new OA\Property(property: 'notes',          type: 'string', nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Updated attendance record'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function update(UpdateAttendanceRequest $request, int $id): AttendanceResource
    {
        $attendance = $this->findService->find($id);
        if (! $attendance) {
            abort(404);
        }
        $this->authorize('update', $attendance);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdateAttendanceData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new AttendanceResource($updated);
    }

    #[OA\Delete(
        path: '/api/hr/attendance/{id}',
        summary: 'Delete attendance record',
        tags: ['HR - Attendance'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $attendance = $this->findService->find($id);
        if (! $attendance) {
            abort(404);
        }
        $this->authorize('delete', $attendance);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Attendance record deleted successfully']);
    }

    #[OA\Get(
        path: '/api/hr/attendance/employee/{employeeId}',
        summary: 'Get attendance records by employee',
        tags: ['HR - Attendance'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Attendance records for the employee')],
    )]
    public function byEmployee(int $employeeId): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);
        $items = $this->findService->getByEmployee($employeeId);

        return response()->json(['data' => AttendanceResource::collection(collect($items))]);
    }
}
