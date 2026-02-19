<?php

declare(strict_types=1);

namespace Modules\JobCard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JobCard\Requests\StoreInspectionItemRequest;
use Modules\JobCard\Requests\StoreJobCardRequest;
use Modules\JobCard\Requests\StoreJobPartRequest;
use Modules\JobCard\Requests\StoreJobTaskRequest;
use Modules\JobCard\Requests\UpdateJobCardRequest;
use Modules\JobCard\Resources\InspectionItemResource;
use Modules\JobCard\Resources\JobCardResource;
use Modules\JobCard\Resources\JobPartResource;
use Modules\JobCard\Resources\JobTaskResource;
use Modules\JobCard\Services\InspectionItemService;
use Modules\JobCard\Services\JobCardService;
use Modules\JobCard\Services\JobPartService;
use Modules\JobCard\Services\JobTaskService;

/**
 * JobCard Controller
 *
 * Handles HTTP requests for JobCard operations
 */
class JobCardController extends Controller
{
    /**
     * JobCardController constructor
     */
    public function __construct(
        private readonly JobCardService $jobCardService,
        private readonly JobTaskService $taskService,
        private readonly InspectionItemService $inspectionService,
        private readonly JobPartService $partService
    ) {}

    /**
     * Display a listing of job cards
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $jobCards = $this->jobCardService->getAll($filters);

        return $this->successResponse(
            JobCardResource::collection($jobCards),
            __('jobcard::messages.job_cards_retrieved')
        );
    }

    /**
     * Store a newly created job card
     */
    public function store(StoreJobCardRequest $request): JsonResponse
    {
        $jobCard = $this->jobCardService->create($request->validated());

        return $this->createdResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.job_card_created')
        );
    }

    /**
     * Display the specified job card
     */
    public function show(int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->getWithRelations($id);

        return $this->successResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.job_card_retrieved')
        );
    }

    /**
     * Update the specified job card
     */
    public function update(UpdateJobCardRequest $request, int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->update($id, $request->validated());

        return $this->successResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.job_card_updated')
        );
    }

    /**
     * Remove the specified job card
     */
    public function destroy(int $id): JsonResponse
    {
        $this->jobCardService->delete($id);

        return $this->successResponse(
            null,
            __('jobcard::messages.job_card_deleted')
        );
    }

    /**
     * Start job card
     */
    public function start(int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->start($id);

        return $this->successResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.job_card_started')
        );
    }

    /**
     * Pause job card
     */
    public function pause(int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->pause($id);

        return $this->successResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.job_card_paused')
        );
    }

    /**
     * Resume job card
     */
    public function resume(int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->resume($id);

        return $this->successResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.job_card_resumed')
        );
    }

    /**
     * Complete job card
     */
    public function complete(int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->complete($id);

        return $this->successResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.job_card_completed')
        );
    }

    /**
     * Update job card status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string'],
        ]);

        $jobCard = $this->jobCardService->updateStatus($id, $request->input('status'));

        return $this->successResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.status_updated')
        );
    }

    /**
     * Assign technician to job card
     */
    public function assignTechnician(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'technician_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $jobCard = $this->jobCardService->assignTechnician($id, $request->input('technician_id'));

        return $this->successResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.technician_assigned')
        );
    }

    /**
     * Calculate totals for job card
     */
    public function calculateTotals(int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->calculateTotals($id);

        return $this->successResponse(
            new JobCardResource($jobCard),
            __('jobcard::messages.totals_calculated')
        );
    }

    /**
     * Get job card statistics
     */
    public function statistics(int $id): JsonResponse
    {
        $statistics = $this->jobCardService->getStatistics($id);

        return $this->successResponse(
            $statistics,
            __('jobcard::messages.statistics_retrieved')
        );
    }

    /**
     * Add task to job card
     */
    public function addTask(StoreJobTaskRequest $request, int $id): JsonResponse
    {
        $task = $this->taskService->addToJobCard($id, $request->validated());

        return $this->createdResponse(
            new JobTaskResource($task),
            __('jobcard::messages.task_added')
        );
    }

    /**
     * Get tasks for job card
     */
    public function getTasks(int $id): JsonResponse
    {
        $tasks = $this->taskService->getForJobCard($id);

        return $this->successResponse(
            JobTaskResource::collection($tasks),
            __('jobcard::messages.tasks_retrieved')
        );
    }

    /**
     * Remove task from job card
     */
    public function removeTask(int $id, int $taskId): JsonResponse
    {
        $this->taskService->delete($taskId);

        return $this->successResponse(
            null,
            __('jobcard::messages.task_removed')
        );
    }

    /**
     * Add inspection item to job card
     */
    public function addInspection(StoreInspectionItemRequest $request, int $id): JsonResponse
    {
        $inspectionItem = $this->inspectionService->addToJobCard($id, $request->validated());

        return $this->createdResponse(
            new InspectionItemResource($inspectionItem),
            __('jobcard::messages.inspection_added')
        );
    }

    /**
     * Get inspection items for job card
     */
    public function getInspections(int $id): JsonResponse
    {
        $inspections = $this->inspectionService->getForJobCard($id);

        return $this->successResponse(
            InspectionItemResource::collection($inspections),
            __('jobcard::messages.inspections_retrieved')
        );
    }

    /**
     * Add part to job card
     */
    public function addPart(StoreJobPartRequest $request, int $id): JsonResponse
    {
        $part = $this->partService->addToJobCard($id, $request->validated());

        return $this->createdResponse(
            new JobPartResource($part),
            __('jobcard::messages.part_added')
        );
    }

    /**
     * Get parts for job card
     */
    public function getParts(int $id): JsonResponse
    {
        $parts = $this->partService->getForJobCard($id);

        return $this->successResponse(
            JobPartResource::collection($parts),
            __('jobcard::messages.parts_retrieved')
        );
    }

    /**
     * Remove part from job card
     */
    public function removePart(int $id, int $partId): JsonResponse
    {
        $this->partService->delete($partId);

        return $this->successResponse(
            null,
            __('jobcard::messages.part_removed')
        );
    }
}
