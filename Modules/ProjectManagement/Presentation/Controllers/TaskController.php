<?php

namespace Modules\ProjectManagement\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\ProjectManagement\Application\UseCases\CompleteTaskUseCase;
use Modules\ProjectManagement\Application\UseCases\CreateTaskUseCase;
use Modules\ProjectManagement\Infrastructure\Repositories\TaskRepository;
use Modules\ProjectManagement\Presentation\Requests\StoreTaskRequest;
use Modules\Shared\Application\ResponseFormatter;

class TaskController extends Controller
{
    public function __construct(
        private CreateTaskUseCase   $createUseCase,
        private CompleteTaskUseCase $completeUseCase,
        private TaskRepository      $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($task, 'Task created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $task = $this->repo->findById($id);
        if (! $task) {
            return ResponseFormatter::error('Task not found.', [], 404);
        }
        return ResponseFormatter::success($task);
    }

    public function update(StoreTaskRequest $request, string $id): JsonResponse
    {
        $task = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($task, 'Task updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Task deleted.');
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $actualHours = $request->input('actual_hours');
        $task = $this->completeUseCase->execute($id, $actualHours !== null ? (string) $actualHours : null);
        return ResponseFormatter::success($task, 'Task completed.');
    }
}
