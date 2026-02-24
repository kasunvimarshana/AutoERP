<?php

namespace Modules\ProjectManagement\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ProjectManagement\Application\UseCases\CreateProjectUseCase;
use Modules\ProjectManagement\Infrastructure\Repositories\ProjectRepository;
use Modules\ProjectManagement\Presentation\Requests\StoreProjectRequest;
use Modules\Shared\Application\ResponseFormatter;

class ProjectController extends Controller
{
    public function __construct(
        private CreateProjectUseCase $createUseCase,
        private ProjectRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($project, 'Project created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $project = $this->repo->findById($id);
        if (! $project) {
            return ResponseFormatter::error('Project not found.', [], 404);
        }
        return ResponseFormatter::success($project);
    }

    public function update(StoreProjectRequest $request, string $id): JsonResponse
    {
        $project = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($project, 'Project updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Project deleted.');
    }
}
