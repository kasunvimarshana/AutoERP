<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Hr\Http\Requests\HrMasterRequest;
use Modules\Hr\Services\HrMasterService;
abstract class HrMasterController
{
    public function __construct(protected readonly HrMasterService $service, private readonly string $dataClass, private readonly string $resourceClass) {}
    public function index(HrMasterRequest $request): AnonymousResourceCollection { return $this->resourceClass::collection($this->service->paginate($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage())); }
    public function store(HrMasterRequest $request): JsonResponse { return (new $this->resourceClass($this->service->create($request->toData($this->dataClass))))->response()->setStatusCode(201); }
    public function show(HrMasterRequest $request, int $id): JsonResource { return new $this->resourceClass($this->service->find($id, $request->tenantId(), $request->organizationUnitId())); }
    public function update(HrMasterRequest $request, int $id): JsonResource { $model = $this->service->find($id, $request->tenantId(), $request->organizationUnitId()); return new $this->resourceClass($this->service->update($model, $request->toData($this->dataClass))); }
    public function destroy(HrMasterRequest $request, int $id): JsonResponse { $this->service->delete($this->service->find($id, $request->tenantId(), $request->organizationUnitId())); return response()->json(null, 204); }
    public function lookup(HrMasterRequest $request): AnonymousResourceCollection { return $this->resourceClass::collection($this->service->lookup($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage())); }
}
