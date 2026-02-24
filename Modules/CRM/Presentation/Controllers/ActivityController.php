<?php
namespace Modules\CRM\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Application\UseCases\CreateActivityUseCase;
use Modules\CRM\Application\UseCases\CompleteActivityUseCase;
use Modules\CRM\Infrastructure\Repositories\ActivityRepository;
use Modules\CRM\Presentation\Requests\StoreActivityRequest;
use Modules\Shared\Application\ResponseFormatter;
class ActivityController extends Controller
{
    public function __construct(
        private CreateActivityUseCase $createUseCase,
        private CompleteActivityUseCase $completeUseCase,
        private ActivityRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreActivityRequest $request): JsonResponse
    {
        $activity = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($activity, 'Activity created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $activity = $this->repo->findById($id);
        if (!$activity) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($activity);
    }
    public function update(StoreActivityRequest $request, string $id): JsonResponse
    {
        $activity = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($activity, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
    public function complete(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['outcome' => 'nullable|string']);
        $activity = $this->completeUseCase->execute($id, $data);
        return ResponseFormatter::success($activity, 'Activity completed.');
    }
}
