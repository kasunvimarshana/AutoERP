<?php

namespace Modules\ProjectManagement\Presentation\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ProjectManagement\Application\UseCases\CreateMilestoneUseCase;
use Modules\ProjectManagement\Infrastructure\Repositories\MilestoneRepository;
use Modules\ProjectManagement\Presentation\Requests\StoreMilestoneRequest;
use Modules\Shared\Application\ResponseFormatter;

class MilestoneController extends Controller
{
    public function __construct(
        private CreateMilestoneUseCase $createUseCase,
        private MilestoneRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreMilestoneRequest $request): JsonResponse
    {
        try {
            $milestone = $this->createUseCase->execute($request->validated());
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }

        return ResponseFormatter::success($milestone, 'Milestone created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $milestone = $this->repo->findById($id);
        if (! $milestone) {
            return ResponseFormatter::error('Milestone not found.', [], 404);
        }

        return ResponseFormatter::success($milestone);
    }

    public function update(StoreMilestoneRequest $request, string $id): JsonResponse
    {
        $milestone = $this->repo->findById($id);
        if (! $milestone) {
            return ResponseFormatter::error('Milestone not found.', [], 404);
        }

        $milestone = $this->repo->update($id, $request->validated());

        return ResponseFormatter::success($milestone, 'Milestone updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $milestone = $this->repo->findById($id);
        if (! $milestone) {
            return ResponseFormatter::error('Milestone not found.', [], 404);
        }

        $this->repo->delete($id);

        return ResponseFormatter::success(null, 'Milestone deleted.');
    }
}
