<?php

namespace App\Modules\JobCards\Http\Controllers;

use App\Core\BaseController;
use App\Modules\JobCards\Services\JobCardTaskService;
use App\Modules\JobCards\Services\JobCardService;
use App\Modules\JobCards\Requests\CreateJobCardTaskRequest;
use App\Modules\JobCards\Requests\UpdateJobCardTaskRequest;
use App\Modules\JobCards\Resources\JobCardTaskResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobCardTaskController extends BaseController
{
    protected JobCardTaskService $service;
    protected JobCardService $jobCardService;

    public function __construct(JobCardTaskService $service, JobCardService $jobCardService)
    {
        $this->service = $service;
        $this->jobCardService = $jobCardService;
    }

    public function index(): JsonResponse
    {
        $tasks = $this->service->getAllTasks();
        return $this->collection(JobCardTaskResource::collection($tasks));
    }

    public function store(CreateJobCardTaskRequest $request): JsonResponse
    {
        $task = $this->service->createTask($request->validated());
        return $this->created(new JobCardTaskResource($task));
    }

    public function show(int $id): JsonResponse
    {
        $task = $this->service->getTask($id);
        if (!$task) {
            return $this->notFound();
        }
        return $this->resource(new JobCardTaskResource($task));
    }

    public function update(UpdateJobCardTaskRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateTask($id, $request->validated());
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Job Card Task updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteTask($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->noContent();
    }

    public function startTask(int $id): JsonResponse
    {
        try {
            $result = $this->service->startTask($id);
            if (!$result) {
                return $this->notFound();
            }
            return $this->success(null, 'Job Card Task started successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function completeTask(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $request->input('user_id');
            $result = $this->jobCardService->completeTask($id, $userId);
            if (!$result) {
                return $this->notFound();
            }
            return $this->success(null, 'Job Card Task completed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getByJobCard(int $jobCardId): JsonResponse
    {
        $tasks = $this->service->getByJobCard($jobCardId);
        return $this->collection(JobCardTaskResource::collection($tasks));
    }

    public function getByTaskType(Request $request): JsonResponse
    {
        $taskType = $request->input('task_type');
        $tasks = $this->service->getByTaskType($taskType);
        return $this->collection(JobCardTaskResource::collection($tasks));
    }

    public function getByStatus(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $tasks = $this->service->getByStatus($status);
        return $this->collection(JobCardTaskResource::collection($tasks));
    }
}
