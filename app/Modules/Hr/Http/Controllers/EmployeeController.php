<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Http\Requests\ChangeEmployeeStatusRequest;
use Modules\Hr\Http\Requests\EmployeeRelationRequest;
use Modules\Hr\Http\Requests\ListEmployeeRequest;
use Modules\Hr\Http\Requests\StoreEmployeeRequest;
use Modules\Hr\Http\Requests\StoreEmployeeWithRelationsRequest;
use Modules\Hr\Http\Requests\UpdateEmployeeRequest;
use Modules\Hr\Http\Resources\EmployeeResource;
use Modules\Hr\Http\Resources\EmployeeSummaryResource;
use Modules\Hr\Services\EmployeeAvailabilityService;
use Modules\Hr\Services\EmployeeCreationService;
use Modules\Hr\Services\EmployeeQueryService;
use Modules\Hr\Services\EmployeeStatusService;
use Modules\Hr\Services\EmployeeUpdateService;

final class EmployeeController
{
    public function __construct(private readonly EmployeeQueryService $queries, private readonly EmployeeCreationService $creation, private readonly EmployeeUpdateService $updates, private readonly EmployeeStatusService $statuses, private readonly EmployeeAvailabilityService $availability) {}
    public function index(ListEmployeeRequest $request): AnonymousResourceCollection { return EmployeeSummaryResource::collection($this->queries->paginate($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage())); }
    public function store(StoreEmployeeRequest $request): JsonResponse { return $this->created($this->creation->create($request->toData())); }
    public function storeWithRelations(StoreEmployeeWithRelationsRequest $request): JsonResponse { return $this->created($this->creation->create($request->toData())); }
    public function show(ListEmployeeRequest $request, int $employee): EmployeeResource { return new EmployeeResource($this->queries->find($employee, $request->tenantId(), $request->organizationUnitId())); }
    public function update(UpdateEmployeeRequest $request, int $employee): EmployeeResource { return new EmployeeResource($this->updates->update($this->queries->employee($employee, $request->tenantId(), $request->organizationUnitId()), $request->toData())); }
    public function destroy(ListEmployeeRequest $request, int $employee): JsonResponse { $this->queries->delete($this->queries->employee($employee, $request->tenantId(), $request->organizationUnitId())); return response()->json(null, 204); }
    public function activate(ListEmployeeRequest $request, int $employee): EmployeeResource { return $this->changeTo($request, $employee, EmployeeStatus::Active); }
    public function deactivate(ListEmployeeRequest $request, int $employee): EmployeeResource { return $this->changeTo($request, $employee, EmployeeStatus::Inactive); }
    public function changeStatus(ChangeEmployeeStatusRequest $request, int $employee): EmployeeResource { return new EmployeeResource($this->statuses->change($this->queries->employee($employee, $request->tenantId(), $request->organizationUnitId()), $request->toData())); }
    public function changeAvailability(EmployeeRelationRequest $request, int $employee): EmployeeResource { return new EmployeeResource($this->availability->updateCurrent($this->queries->employee($employee, $request->tenantId(), $request->organizationUnitId()), $request->availabilityData($request->validated()))); }
    public function lookup(ListEmployeeRequest $request, ?string $kind = null): AnonymousResourceCollection { return EmployeeSummaryResource::collection($this->queries->lookup($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(), $kind ?? 'all')); }
    private function changeTo(ListEmployeeRequest $request, int $id, EmployeeStatus $status): EmployeeResource { return new EmployeeResource($this->statuses->changeTo($this->queries->employee($id, $request->tenantId(), $request->organizationUnitId()), $status, $request->currentUserId())); }
    private function created($employee): JsonResponse { return (new EmployeeResource($employee))->response()->setStatusCode(201); }
}
