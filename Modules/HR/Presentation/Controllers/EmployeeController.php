<?php

namespace Modules\HR\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\UseCases\CreateEmployeeUseCase;
use Modules\HR\Infrastructure\Repositories\EmployeeRepository;
use Modules\HR\Presentation\Requests\StoreEmployeeRequest;
use Modules\Shared\Application\ResponseFormatter;

class EmployeeController extends Controller
{
    public function __construct(
        private CreateEmployeeUseCase $createUseCase,
        private EmployeeRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($employee, 'Employee created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $employee = $this->repo->findById($id);
        if (! $employee) {
            return ResponseFormatter::error('Employee not found.', [], 404);
        }
        return ResponseFormatter::success($employee);
    }

    public function update(StoreEmployeeRequest $request, string $id): JsonResponse
    {
        $employee = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($employee, 'Employee updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Employee deleted.');
    }
}
