<?php

namespace Modules\HR\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\UseCases\CreateSalaryComponentUseCase;
use Modules\HR\Infrastructure\Repositories\SalaryComponentRepository;
use Modules\HR\Presentation\Requests\StoreSalaryComponentRequest;
use Modules\Shared\Application\ResponseFormatter;

class SalaryComponentController extends Controller
{
    public function __construct(
        private CreateSalaryComponentUseCase $createUseCase,
        private SalaryComponentRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated(
            $this->repo->paginate(request()->all(), 15)
        );
    }

    public function store(StoreSalaryComponentRequest $request): JsonResponse
    {
        try {
            $component = $this->createUseCase->execute($request->validated());
            return ResponseFormatter::success($component, 'Salary component created.', 201);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $component = $this->repo->findById($id);
        if (! $component) {
            return ResponseFormatter::error('Salary component not found.', [], 404);
        }
        return ResponseFormatter::success($component);
    }

    public function update(StoreSalaryComponentRequest $request, string $id): JsonResponse
    {
        $component = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($component, 'Salary component updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Salary component deleted.');
    }
}
