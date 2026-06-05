<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Invoice\Presentation\Http\Resources\InvoiceResource;
use Modules\VehicleService\Application\Services\VehicleServiceService;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertJobCardRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertServiceTypeRequest;
use Modules\VehicleService\Presentation\Http\Resources\JobCardResource;
use Modules\VehicleService\Presentation\Http\Resources\ServiceTypeResource;

final class VehicleServiceController extends Controller
{
    public function __construct(private readonly VehicleServiceService $vehicleServices) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->vehicleServices->dashboard()]);
    }

    public function lookup(ListVehicleServiceRequest $request, string $type): JsonResponse
    {
        return response()->json(['data' => $this->vehicleServices->lookup($type, $request->validated())]);
    }

    public function serviceTypes(ListVehicleServiceRequest $request)
    {
        return ServiceTypeResource::collection($this->vehicleServices->paginateServiceTypes($request->validated()));
    }

    public function showServiceType(int $serviceType): ServiceTypeResource
    {
        return new ServiceTypeResource($this->vehicleServices->findServiceType($serviceType));
    }

    public function storeServiceType(UpsertServiceTypeRequest $request)
    {
        return (new ServiceTypeResource($this->vehicleServices->createServiceType($request->validated())))->response()->setStatusCode(201);
    }

    public function updateServiceType(UpsertServiceTypeRequest $request, int $serviceType): ServiceTypeResource
    {
        return new ServiceTypeResource($this->vehicleServices->updateServiceType($serviceType, $request->validated()));
    }

    public function destroyServiceType(int $serviceType): JsonResponse
    {
        $this->vehicleServices->deleteServiceType($serviceType);

        return response()->json(null, 204);
    }

    public function jobs(ListVehicleServiceRequest $request)
    {
        return JobCardResource::collection($this->vehicleServices->paginateJobs($request->validated()));
    }

    public function showJob(int $jobCard): JobCardResource
    {
        return new JobCardResource($this->vehicleServices->findJob($jobCard));
    }

    public function storeJob(UpsertJobCardRequest $request)
    {
        return (new JobCardResource($this->vehicleServices->createJob($request->validated())))->response()->setStatusCode(201);
    }

    public function updateJob(UpsertJobCardRequest $request, int $jobCard): JobCardResource
    {
        return new JobCardResource($this->vehicleServices->updateJob($jobCard, $request->validated()));
    }

    public function startJob(int $jobCard): JobCardResource
    {
        return new JobCardResource($this->vehicleServices->startJob($jobCard));
    }

    public function consumeInventory(int $jobCard): JobCardResource
    {
        return new JobCardResource($this->vehicleServices->consumeInventory($jobCard));
    }

    public function completeJob(int $jobCard): JobCardResource
    {
        return new JobCardResource($this->vehicleServices->completeJob($jobCard));
    }

    public function cancelJob(int $jobCard): JobCardResource
    {
        return new JobCardResource($this->vehicleServices->cancelJob($jobCard, request('reason')));
    }

    public function invoiceJob(int $jobCard): InvoiceResource
    {
        return new InvoiceResource($this->vehicleServices->createInvoice($jobCard));
    }

    public function destroyJob(int $jobCard): JsonResponse
    {
        $this->vehicleServices->deleteJob($jobCard);

        return response()->json(null, 204);
    }
}
