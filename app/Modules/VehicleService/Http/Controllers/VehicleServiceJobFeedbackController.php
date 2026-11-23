<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleService\Http\Requests\DeleteVehicleServiceJobFeedbackRequest;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobFeedbacksRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceJobFeedbackRequest;
use Modules\VehicleService\Http\Requests\UpdateVehicleServiceJobFeedbackRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceJobFeedbackResource;
use Modules\VehicleService\Services\VehicleServiceJobFeedbackService;

final class VehicleServiceJobFeedbackController extends VehicleServiceController
{
    public function __construct(
        private readonly VehicleServiceJobFeedbackService $service,
    ) {}

    public function index(ListVehicleServiceJobFeedbacksRequest $request, int $job): JsonResponse
    {
        $jobModel = $this->job($request, $job);
        $feedbacks = $this->service->list($jobModel, $request->validated());
        $summary = $this->service->summary($jobModel);

        return response()->json([
            'data' => VehicleServiceJobFeedbackResource::collection($feedbacks),
            'meta' => [
                'summary' => $summary,
            ],
        ]);
    }

    public function summary(ListVehicleServiceJobFeedbacksRequest $request, int $job): JsonResponse
    {
        $jobModel = $this->job($request, $job);

        return response()->json([
            'data' => $this->service->summary($jobModel),
        ]);
    }

    public function show(ListVehicleServiceJobFeedbacksRequest $request, int $job, int $feedback): VehicleServiceJobFeedbackResource
    {
        $jobModel = $this->job($request, $job);
        $feedbackModel = $this->feedback($jobModel, $feedback)->loadMissing([
            'user:id,first_name,last_name,email,username',
            'creator:id,first_name,last_name',
            'updater:id,first_name,last_name',
        ]);

        return new VehicleServiceJobFeedbackResource($feedbackModel);
    }

    public function store(StoreVehicleServiceJobFeedbackRequest $request, int $job): JsonResponse
    {
        $jobModel = $this->job($request, $job);
        $created = $this->service->create(
            $jobModel,
            $request->toData($request->currentUserId()),
            (int) $request->currentUserId(),
        );

        return (new VehicleServiceJobFeedbackResource($created))->response()->setStatusCode(201);
    }

    public function update(UpdateVehicleServiceJobFeedbackRequest $request, int $job, int $feedback): JsonResponse
    {
        $jobModel = $this->job($request, $job);
        $feedbackModel = $this->feedback($jobModel, $feedback);
        $updated = $this->service->update(
            $jobModel,
            $feedbackModel,
            $request->toData($request->currentUserId()),
            (int) $request->currentUserId(),
        );

        return (new VehicleServiceJobFeedbackResource($updated))->response()->setStatusCode(200);
    }

    public function destroy(DeleteVehicleServiceJobFeedbackRequest $request, int $job, int $feedback): JsonResponse
    {
        $jobModel = $this->job($request, $job);
        $feedbackModel = $this->feedback($jobModel, $feedback);
        $expectedVersion = $request->filled('expected_version') ? (int) $request->input('expected_version') : null;
        $expectedJobVersion = $request->filled('expected_job_version') ? (int) $request->input('expected_job_version') : null;

        $this->service->delete($jobModel, $feedbackModel, (int) $request->currentUserId(), $expectedVersion, $expectedJobVersion);

        return response()->json(status: 204);
    }
}
