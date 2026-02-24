<?php

namespace Modules\HR\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\UseCases\AssignSalaryStructureUseCase;
use Modules\HR\Application\UseCases\CreateSalaryStructureUseCase;
use Modules\HR\Infrastructure\Repositories\SalaryStructureAssignmentRepository;
use Modules\HR\Infrastructure\Repositories\SalaryStructureRepository;
use Modules\HR\Presentation\Requests\StoreSalaryStructureAssignmentRequest;
use Modules\HR\Presentation\Requests\StoreSalaryStructureRequest;
use Modules\Shared\Application\ResponseFormatter;

class SalaryStructureController extends Controller
{
    public function __construct(
        private CreateSalaryStructureUseCase  $createUseCase,
        private AssignSalaryStructureUseCase  $assignUseCase,
        private SalaryStructureRepository     $repo,
        private SalaryStructureAssignmentRepository $assignmentRepo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated(
            $this->repo->paginate(request()->all(), 15)
        );
    }

    public function store(StoreSalaryStructureRequest $request): JsonResponse
    {
        try {
            $structure = $this->createUseCase->execute($request->validated());
            return ResponseFormatter::success($structure, 'Salary structure created.', 201);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $structure = $this->repo->findWithLines($id);
        if (! $structure) {
            return ResponseFormatter::error('Salary structure not found.', [], 404);
        }
        return ResponseFormatter::success($structure);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Salary structure deleted.');
    }

    public function assign(StoreSalaryStructureAssignmentRequest $request, string $id): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'structure_id' => $id,
                'tenant_id'    => auth()->user()?->tenant_id,
            ]);
            $assignment = $this->assignUseCase->execute($data);
            return ResponseFormatter::success($assignment, 'Salary structure assigned.', 201);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function assignments(): JsonResponse
    {
        return ResponseFormatter::paginated(
            $this->assignmentRepo->paginate(request()->all(), 15)
        );
    }
}
