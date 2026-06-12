<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Http\Requests\CreateVehicleServiceInvoiceRequest;
use Modules\VehicleService\Http\Requests\IssueVehicleServiceInventoryRequest;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\PrepareVehicleServicePaymentRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceDocumentRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceEmployeeRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceInspectionRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceLineRequest;
use Modules\VehicleService\Http\Requests\VehicleServiceActionRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceDocumentResource;
use Modules\VehicleService\Http\Resources\VehicleServiceEmployeeAssignmentResource;
use Modules\VehicleService\Http\Resources\VehicleServiceInspectionResource;
use Modules\VehicleService\Http\Resources\VehicleServiceJobLineResource;
use Modules\VehicleService\Http\Resources\VehicleServiceJobResource;
use Modules\VehicleService\Models\VehicleServiceDocument;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;
use Modules\VehicleService\Services\VehicleServiceEmployeeAssignmentService;
use Modules\VehicleService\Services\VehicleServiceInspectionService;
use Modules\VehicleService\Services\VehicleServiceInventoryIntegrationService;
use Modules\VehicleService\Services\VehicleServiceInvoiceIntegrationService;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceLineService;
use Modules\VehicleService\Services\VehicleServicePaymentIntegrationService;
use Modules\VehicleService\Services\VehicleServiceStatusService;

final class VehicleServiceController
{
    public function index(ListVehicleServiceJobRequest $request, VehicleServiceJobService $service): AnonymousResourceCollection
    {
        $query = $this->scope(VehicleServiceJob::query(), $request)
            ->with(['customer', 'vehicle.make', 'vehicle.model', 'vehicle.customer', 'supervisor']);
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('job_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle->where('registration_number', 'like', "%{$search}%")->orWhere('vehicle_number', 'like', "%{$search}%"));
            });
        }
        foreach (['status', 'customer_id', 'vehicle_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('job_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('job_date', '<=', $request->input('date_to'));
        }

        return VehicleServiceJobResource::collection($query->latest('job_date')->latest('id')->paginate($request->perPage()));
    }

    public function lookup(ListVehicleServiceJobRequest $request): JsonResponse
    {
        $query = $this->scope(VehicleServiceJob::query(), $request)->with(['customer', 'vehicle']);
        if ($request->filled('search')) {
            $query->where('job_number', 'like', '%'.trim((string) $request->input('search')).'%');
        }

        return response()->json(['data' => $query->latest('id')->limit($request->perPage())->get()->map(fn (VehicleServiceJob $job) => [
            'id' => (int) $job->getKey(),
            'code' => $job->job_number,
            'name' => $job->job_number.' - '.($job->vehicle?->registration_number ?? $job->customer?->display_name ?? 'Service job'),
        ])->all()]);
    }

    public function store(StoreVehicleServiceJobRequest $request, VehicleServiceJobService $service): JsonResponse
    {
        return (new VehicleServiceJobResource($service->create($request->toData())))
            ->response()->setStatusCode(201);
    }

    public function show(ListVehicleServiceJobRequest $request, int $job, VehicleServiceJobService $service): VehicleServiceJobResource
    {
        return new VehicleServiceJobResource($this->job($request, $job)->load($service->relations()));
    }

    public function update(StoreVehicleServiceJobRequest $request, int $job, VehicleServiceJobService $service): VehicleServiceJobResource
    {
        return new VehicleServiceJobResource($service->update($this->job($request, $job), $request->toData()));
    }

    public function destroy(VehicleServiceActionRequest $request, int $job, VehicleServiceJobService $service): JsonResponse
    {
        $service->delete($this->job($request, $job));

        return response()->json(status: 204);
    }

    public function inspect(StoreVehicleServiceInspectionRequest $request, int $job, VehicleServiceInspectionService $service): VehicleServiceInspectionResource
    {
        return new VehicleServiceInspectionResource($service->save($this->job($request, $job), $request->toData(true)));
    }

    public function start(VehicleServiceActionRequest $request, int $job, VehicleServiceStatusService $service): VehicleServiceJobResource
    {
        return new VehicleServiceJobResource($service->change($this->job($request, $job), VehicleServiceJobStatus::InProgress, $request->currentUserId(), $request->input('reason')));
    }

    public function complete(VehicleServiceActionRequest $request, int $job, VehicleServiceStatusService $service): VehicleServiceJobResource
    {
        return new VehicleServiceJobResource($service->change($this->job($request, $job), VehicleServiceJobStatus::Completed, $request->currentUserId(), $request->input('reason')));
    }

    public function cancel(VehicleServiceActionRequest $request, int $job, VehicleServiceStatusService $service): VehicleServiceJobResource
    {
        return new VehicleServiceJobResource($service->change($this->job($request, $job), VehicleServiceJobStatus::Cancelled, $request->currentUserId(), $request->input('reason')));
    }

    public function inspection(ListVehicleServiceJobRequest $request, int $job): JsonResponse|VehicleServiceInspectionResource
    {
        $inspection = $this->job($request, $job)->inspection()->with('inspector')->first();

        return $inspection === null ? response()->json(['data' => null]) : new VehicleServiceInspectionResource($inspection);
    }

    public function updateInspection(StoreVehicleServiceInspectionRequest $request, int $job, VehicleServiceInspectionService $service): VehicleServiceInspectionResource
    {
        return new VehicleServiceInspectionResource($service->save($this->job($request, $job), $request->toData()));
    }

    public function lines(ListVehicleServiceJobRequest $request, int $job): AnonymousResourceCollection
    {
        return VehicleServiceJobLineResource::collection($this->job($request, $job)->lines()
            ->whereNull('parent_line_id')->with(['item', 'variant', 'uom', 'children.item', 'children.uom', 'employeeAssignments.employee'])->get());
    }

    public function storeLine(StoreVehicleServiceLineRequest $request, int $job, VehicleServiceLineService $service): JsonResponse
    {
        return (new VehicleServiceJobLineResource($service->create($this->job($request, $job), $request->toData())))
            ->response()->setStatusCode(201);
    }

    public function updateLine(StoreVehicleServiceLineRequest $request, int $job, int $line, VehicleServiceLineService $service): VehicleServiceJobLineResource
    {
        $model = $this->line($this->job($request, $job), $line);

        return new VehicleServiceJobLineResource($service->update($model->job, $model, $request->toData()));
    }

    public function destroyLine(VehicleServiceActionRequest $request, int $job, int $line, VehicleServiceLineService $service): JsonResponse
    {
        $jobModel = $this->job($request, $job);
        $service->delete($jobModel, $this->line($jobModel, $line));

        return response()->json(status: 204);
    }

    public function employees(ListVehicleServiceJobRequest $request, int $job, int $line): AnonymousResourceCollection
    {
        $jobModel = $this->job($request, $job);

        return VehicleServiceEmployeeAssignmentResource::collection(
            $this->line($jobModel, $line)->employeeAssignments()->with('employee')->get(),
        );
    }

    public function storeEmployee(StoreVehicleServiceEmployeeRequest $request, int $job, int $line, VehicleServiceEmployeeAssignmentService $service): JsonResponse
    {
        $jobModel = $this->job($request, $job);

        return (new VehicleServiceEmployeeAssignmentResource($service->create($jobModel, $this->line($jobModel, $line), $request->toData())))
            ->response()->setStatusCode(201);
    }

    public function updateEmployee(StoreVehicleServiceEmployeeRequest $request, int $job, int $line, int $assignment, VehicleServiceEmployeeAssignmentService $service): VehicleServiceEmployeeAssignmentResource
    {
        $jobModel = $this->job($request, $job);
        $lineModel = $this->line($jobModel, $line);

        return new VehicleServiceEmployeeAssignmentResource($service->update(
            $jobModel,
            $lineModel,
            $this->assignment($lineModel, $assignment),
            $request->toData(),
        ));
    }

    public function destroyEmployee(VehicleServiceActionRequest $request, int $job, int $line, int $assignment, VehicleServiceEmployeeAssignmentService $service): JsonResponse
    {
        $jobModel = $this->job($request, $job);
        $lineModel = $this->line($jobModel, $line);
        $service->delete($jobModel, $lineModel, $this->assignment($lineModel, $assignment));

        return response()->json(status: 204);
    }

    public function issueInventory(IssueVehicleServiceInventoryRequest $request, int $job, VehicleServiceInventoryIntegrationService $service): JsonResponse
    {
        $movements = $service->issue(
            $this->job($request, $job),
            (int) $request->input('warehouse_id'),
            $request->filled('warehouse_location_id') ? (int) $request->input('warehouse_location_id') : null,
            array_map('intval', $request->input('line_ids', [])),
            $request->currentUserId(),
        );

        return response()->json(['data' => array_map(fn ($movement) => [
            'id' => (int) $movement->getKey(),
            'movement_number' => $movement->movement_number,
            'source_line_id' => $movement->source_line_id,
            'quantity' => (string) $movement->quantity,
            'status' => $movement->status instanceof \BackedEnum ? $movement->status->value : $movement->status,
        ], $movements)]);
    }

    public function previewInvoice(CreateVehicleServiceInvoiceRequest $request, int $job, VehicleServiceInvoiceIntegrationService $service): JsonResponse
    {
        return response()->json(['data' => get_object_vars($service->preview(
            $this->job($request, $job),
            (string) $request->input('invoice_date'),
            $request->lineQuantities(),
        ))]);
    }

    public function createInvoice(CreateVehicleServiceInvoiceRequest $request, int $job, VehicleServiceInvoiceIntegrationService $service): JsonResponse
    {
        return response()->json(['data' => $service->create(
            $this->job($request, $job),
            (string) $request->input('invoice_date'),
            $request->lineQuantities(),
            $request->filled('due_date') ? (string) $request->input('due_date') : null,
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            (string) $request->input('exchange_rate', '1.000000'),
            $request->filled('notes') ? (string) $request->input('notes') : null,
            $request->currentUserId(),
        )], 201);
    }

    public function preparePayment(PrepareVehicleServicePaymentRequest $request, int $job, VehicleServicePaymentIntegrationService $service): JsonResponse
    {
        return response()->json(['data' => $service->prepare(
            $this->job($request, $job),
            (int) $request->input('invoice_id'),
            (string) $request->input('payment_date'),
            (string) $request->input('amount'),
            $request->filled('payment_method_id') ? (int) $request->input('payment_method_id') : null,
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            (string) $request->input('exchange_rate', '1.000000'),
            $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            $request->currentUserId(),
        )]);
    }

    public function createPayment(PrepareVehicleServicePaymentRequest $request, int $job, VehicleServicePaymentIntegrationService $service): JsonResponse
    {
        return (new PaymentResource($service->create(
            $this->job($request, $job),
            (int) $request->input('invoice_id'),
            (string) $request->input('payment_date'),
            (string) $request->input('amount'),
            $request->filled('payment_method_id') ? (int) $request->input('payment_method_id') : null,
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            (string) $request->input('exchange_rate', '1.000000'),
            $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            $request->currentUserId(),
        )))->response()->setStatusCode(201);
    }

    public function documents(ListVehicleServiceJobRequest $request, int $job): AnonymousResourceCollection
    {
        return VehicleServiceDocumentResource::collection($this->job($request, $job)->documents()->latest()->get());
    }

    public function storeDocument(StoreVehicleServiceDocumentRequest $request, int $job): JsonResponse
    {
        $jobModel = $this->job($request, $job);
        $path = $request->hasFile('file')
            ? $request->file('file')->store('vehicle-service-documents', 'public')
            : ($request->filled('file_path') ? (string) $request->input('file_path') : null);
        $document = VehicleServiceDocument::query()->create([
            'tenant_id' => $jobModel->tenant_id,
            'organization_unit_id' => $jobModel->organization_unit_id,
            'vehicle_service_job_id' => $jobModel->getKey(),
            'document_type' => $request->input('document_type'),
            'file_path' => $path,
            'description' => $request->input('description'),
            'uploaded_by' => $request->currentUserId(),
        ]);

        return (new VehicleServiceDocumentResource($document))->response()->setStatusCode(201);
    }

    public function destroyDocument(VehicleServiceActionRequest $request, int $job, int $document): JsonResponse
    {
        $model = $this->job($request, $job)->documents()->findOrFail($document);
        if ($model->file_path !== null && str_starts_with($model->file_path, 'vehicle-service-documents/')) {
            Storage::disk('public')->delete($model->file_path);
        }
        $model->delete();

        return response()->json(status: 204);
    }

    public function statusHistory(ListVehicleServiceJobRequest $request, int $job): JsonResponse
    {
        return response()->json(['data' => $this->job($request, $job)->statusHistories()->get()->map(fn ($history) => [
            'id' => (int) $history->getKey(),
            'old_status' => $history->old_status instanceof \BackedEnum ? $history->old_status->value : $history->old_status,
            'new_status' => $history->new_status instanceof \BackedEnum ? $history->new_status->value : $history->new_status,
            'reason' => $history->reason,
            'changed_by' => $history->changed_by,
            'changed_at' => $history->changed_at?->toISOString(),
        ])->all()]);
    }

    public function billableLines(ListVehicleServiceJobRequest $request, int $job, VehicleServiceInvoiceIntegrationService $service): AnonymousResourceCollection
    {
        return VehicleServiceJobLineResource::collection($service->billableLines($this->job($request, $job)));
    }

    public function inventoryIssueLines(ListVehicleServiceJobRequest $request, int $job, VehicleServiceInventoryIntegrationService $service): AnonymousResourceCollection
    {
        return VehicleServiceJobLineResource::collection($service->issueLines(
            $this->job($request, $job),
            $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null,
            $request->filled('warehouse_location_id') ? (int) $request->input('warehouse_location_id') : null,
        ));
    }

    public function employeeAssignableLines(ListVehicleServiceJobRequest $request, int $job): AnonymousResourceCollection
    {
        return VehicleServiceJobLineResource::collection($this->job($request, $job)->lines()
            ->where('is_employee_assignable', true)->with(['item', 'variant', 'uom', 'employeeAssignments.employee'])->get());
    }

    private function job(TenantScopedRequest $request, int $id): VehicleServiceJob
    {
        return $this->scope(VehicleServiceJob::query(), $request)->findOrFail($id);
    }

    private function line(VehicleServiceJob $job, int $id): VehicleServiceJobLine
    {
        return $job->lines()->with('job')->findOrFail($id);
    }

    private function assignment(VehicleServiceJobLine $line, int $id): VehicleServiceLineEmployee
    {
        return $line->employeeAssignments()->findOrFail($id);
    }

    private function scope(Builder $query, TenantScopedRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }
}
