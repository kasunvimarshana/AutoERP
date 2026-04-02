<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreatePayrollServiceInterface;
use Modules\HR\Application\Contracts\DeletePayrollServiceInterface;
use Modules\HR\Application\Contracts\FindPayrollServiceInterface;
use Modules\HR\Application\Contracts\ProcessPayrollServiceInterface;
use Modules\HR\Application\Contracts\UpdatePayrollServiceInterface;
use Modules\HR\Application\DTOs\PayrollData;
use Modules\HR\Application\DTOs\UpdatePayrollData;
use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Infrastructure\Http\Requests\StorePayrollRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdatePayrollRequest;
use Modules\HR\Infrastructure\Http\Resources\PayrollCollection;
use Modules\HR\Infrastructure\Http\Resources\PayrollResource;
use OpenApi\Attributes as OA;

class PayrollController extends AuthorizedController
{
    public function __construct(
        protected FindPayrollServiceInterface $findService,
        protected CreatePayrollServiceInterface $createService,
        protected UpdatePayrollServiceInterface $updateService,
        protected DeletePayrollServiceInterface $deleteService,
        protected ProcessPayrollServiceInterface $processService,
    ) {}

    #[OA\Get(
        path: '/api/hr/payroll',
        summary: 'List payroll records',
        tags: ['HR - Payroll'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'employee_id',   in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status',         in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page',       in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',           in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',           in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of payroll records'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): PayrollCollection
    {
        $this->authorize('viewAny', PayrollRecord::class);
        $filters = $request->only(['employee_id', 'status']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');
        $records = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new PayrollCollection($records);
    }

    #[OA\Post(
        path: '/api/hr/payroll',
        summary: 'Create payroll record',
        tags: ['HR - Payroll'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['tenant_id', 'employee_id', 'pay_period_start', 'pay_period_end', 'gross_salary', 'net_salary'],
            properties: [
                new OA\Property(property: 'tenant_id',        type: 'integer'),
                new OA\Property(property: 'employee_id',      type: 'integer'),
                new OA\Property(property: 'pay_period_start', type: 'string', format: 'date'),
                new OA\Property(property: 'pay_period_end',   type: 'string', format: 'date'),
                new OA\Property(property: 'gross_salary',     type: 'number'),
                new OA\Property(property: 'net_salary',       type: 'number'),
                new OA\Property(property: 'deductions',       type: 'number', nullable: true),
                new OA\Property(property: 'allowances',       type: 'number', nullable: true),
                new OA\Property(property: 'bonuses',          type: 'number', nullable: true),
                new OA\Property(property: 'currency',         type: 'string', nullable: true),
                new OA\Property(property: 'status',           type: 'string', enum: ['draft', 'processed', 'paid']),
                new OA\Property(property: 'notes',            type: 'string', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Payroll record created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(StorePayrollRequest $request): JsonResponse
    {
        $this->authorize('create', PayrollRecord::class);
        $dto    = PayrollData::fromArray($request->validated());
        $record = $this->createService->execute($dto->toArray());

        return (new PayrollResource($record))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/hr/payroll/{id}',
        summary: 'Get payroll record',
        tags: ['HR - Payroll'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Payroll record details'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(int $id): PayrollResource
    {
        $record = $this->findService->find($id);
        if (! $record) {
            abort(404);
        }
        $this->authorize('view', $record);

        return new PayrollResource($record);
    }

    #[OA\Put(
        path: '/api/hr/payroll/{id}',
        summary: 'Update payroll record',
        tags: ['HR - Payroll'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'pay_period_start', type: 'string', format: 'date'),
            new OA\Property(property: 'pay_period_end',   type: 'string', format: 'date'),
            new OA\Property(property: 'gross_salary',     type: 'number'),
            new OA\Property(property: 'net_salary',       type: 'number'),
            new OA\Property(property: 'deductions',       type: 'number', nullable: true),
            new OA\Property(property: 'allowances',       type: 'number', nullable: true),
            new OA\Property(property: 'bonuses',          type: 'number', nullable: true),
            new OA\Property(property: 'currency',         type: 'string', nullable: true),
            new OA\Property(property: 'notes',            type: 'string', nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Updated payroll record'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function update(UpdatePayrollRequest $request, int $id): PayrollResource
    {
        $record = $this->findService->find($id);
        if (! $record) {
            abort(404);
        }
        $this->authorize('update', $record);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdatePayrollData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new PayrollResource($updated);
    }

    #[OA\Delete(
        path: '/api/hr/payroll/{id}',
        summary: 'Delete payroll record',
        tags: ['HR - Payroll'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $record = $this->findService->find($id);
        if (! $record) {
            abort(404);
        }
        $this->authorize('delete', $record);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Payroll record deleted successfully']);
    }

    #[OA\Post(
        path: '/api/hr/payroll/{id}/process',
        summary: 'Process payroll record',
        tags: ['HR - Payroll'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Payroll record processed'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function process(int $id): PayrollResource
    {
        $record = $this->findService->find($id);
        if (! $record) {
            abort(404);
        }
        $this->authorize('update', $record);
        $processed = $this->processService->execute(['id' => $id]);

        return new PayrollResource($processed);
    }

    #[OA\Get(
        path: '/api/hr/payroll/employee/{employeeId}',
        summary: 'Get payroll records by employee',
        tags: ['HR - Payroll'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Payroll records for the employee')],
    )]
    public function byEmployee(int $employeeId): JsonResponse
    {
        $this->authorize('viewAny', PayrollRecord::class);
        $items = $this->findService->getByEmployee($employeeId);

        return response()->json(['data' => PayrollResource::collection(collect($items))]);
    }
}
