<?php

namespace Modules\HR\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\UseCases\CreateDepartmentUseCase;
use Modules\HR\Infrastructure\Repositories\DepartmentRepository;
use Modules\HR\Presentation\Requests\StoreDepartmentRequest;
use Modules\Shared\Application\ResponseFormatter;

class DepartmentController extends Controller
{
    public function __construct(
        private CreateDepartmentUseCase $createUseCase,
        private DepartmentRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($department, 'Department created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $department = $this->repo->findById($id);
        if (! $department) {
            return ResponseFormatter::error('Department not found.', [], 404);
        }
        return ResponseFormatter::success($department);
    }

    public function update(StoreDepartmentRequest $request, string $id): JsonResponse
    {
        $department = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($department, 'Department updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Department deleted.');
    }
}
