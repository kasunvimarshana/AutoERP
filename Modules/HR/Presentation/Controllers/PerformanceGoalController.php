<?php

namespace Modules\HR\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\UseCases\CompletePerformanceGoalUseCase;
use Modules\HR\Application\UseCases\CreatePerformanceGoalUseCase;
use Modules\HR\Infrastructure\Repositories\PerformanceGoalRepository;
use Modules\HR\Presentation\Requests\StorePerformanceGoalRequest;
use Modules\Shared\Application\ResponseFormatter;

class PerformanceGoalController extends Controller
{
    public function __construct(
        private CreatePerformanceGoalUseCase   $createUseCase,
        private CompletePerformanceGoalUseCase $completeUseCase,
        private PerformanceGoalRepository      $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated(
            $this->repo->paginate(request()->all(), 15)
        );
    }

    public function store(StorePerformanceGoalRequest $request): JsonResponse
    {
        $goal = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($goal, 'Performance goal created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $goal = $this->repo->findById($id);
        if (! $goal) {
            return ResponseFormatter::error('Performance goal not found.', [], 404);
        }
        return ResponseFormatter::success($goal);
    }

    public function update(StorePerformanceGoalRequest $request, string $id): JsonResponse
    {
        $goal = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($goal, 'Performance goal updated.');
    }

    public function complete(string $id): JsonResponse
    {
        try {
            $goal = $this->completeUseCase->execute($id);
            return ResponseFormatter::success($goal, 'Performance goal completed.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Performance goal deleted.');
    }
}
