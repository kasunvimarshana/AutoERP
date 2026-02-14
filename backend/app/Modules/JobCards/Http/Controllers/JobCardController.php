<?php

namespace App\Modules\JobCards\Http\Controllers;

use App\Core\BaseController;
use App\Modules\JobCards\Services\JobCardService;
use App\Modules\JobCards\Requests\CreateJobCardRequest;
use App\Modules\JobCards\Requests\UpdateJobCardRequest;
use App\Modules\JobCards\Resources\JobCardResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobCardController extends BaseController
{
    protected JobCardService $service;

    public function __construct(JobCardService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $jobCards = $this->service->getAllJobCards();
        return $this->collection(JobCardResource::collection($jobCards));
    }

    public function store(CreateJobCardRequest $request): JsonResponse
    {
        $jobCard = $this->service->createJobCard($request->validated());
        return $this->created(new JobCardResource($jobCard));
    }

    public function show(int $id): JsonResponse
    {
        $jobCard = $this->service->getJobCard($id);
        if (!$jobCard) {
            return $this->notFound();
        }
        return $this->resource(new JobCardResource($jobCard));
    }

    public function update(UpdateJobCardRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateJobCard($id, $request->validated());
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Job Card updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteJobCard($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->noContent();
    }

    public function startWork(int $id): JsonResponse
    {
        try {
            $result = $this->service->startWork($id);
            if (!$result) {
                return $this->notFound();
            }
            return $this->success(null, 'Job Card work started successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function close(int $id): JsonResponse
    {
        try {
            $result = $this->service->closeJobCard($id);
            if (!$result) {
                return $this->notFound();
            }
            return $this->success(null, 'Job Card closed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->service->cancelJobCard($id, $request->input('reason'));
            if (!$result) {
                return $this->notFound();
            }
            return $this->success(null, 'Job Card cancelled successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getByStatus(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $jobCards = $this->service->getByStatus($status);
        return $this->collection(JobCardResource::collection($jobCards));
    }

    public function getByCustomer(int $customerId): JsonResponse
    {
        $jobCards = $this->service->getByCustomer($customerId);
        return $this->collection(JobCardResource::collection($jobCards));
    }

    public function getByVehicle(int $vehicleId): JsonResponse
    {
        $jobCards = $this->service->getByVehicle($vehicleId);
        return $this->collection(JobCardResource::collection($jobCards));
    }

    public function getByTechnician(int $technicianId): JsonResponse
    {
        $jobCards = $this->service->getByTechnician($technicianId);
        return $this->collection(JobCardResource::collection($jobCards));
    }

    public function getByDateRange(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $jobCards = $this->service->getByDateRange($startDate, $endDate);
        return $this->collection(JobCardResource::collection($jobCards));
    }

    public function getTotalCost(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $total = $this->service->getTotalCost($tenantId, $startDate, $endDate);
        return $this->success(['total_cost' => number_format((float) $total, 2, '.', '')]);
    }
}
