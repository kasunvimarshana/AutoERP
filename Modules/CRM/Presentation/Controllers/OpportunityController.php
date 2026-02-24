<?php
namespace Modules\CRM\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Application\UseCases\UpdateOpportunityStageUseCase;
use Modules\CRM\Infrastructure\Repositories\OpportunityRepository;
use Modules\CRM\Presentation\Requests\StoreOpportunityRequest;
use Modules\Shared\Application\ResponseFormatter;
class OpportunityController extends Controller
{
    public function __construct(
        private UpdateOpportunityStageUseCase $stageUseCase,
        private OpportunityRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        $opp = $this->repo->create($request->validated());
        return ResponseFormatter::success($opp, 'Opportunity created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $opp = $this->repo->findById($id);
        if (!$opp) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($opp);
    }
    public function update(StoreOpportunityRequest $request, string $id): JsonResponse
    {
        $opp = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($opp, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
    public function changeStage(Request $request, string $id): JsonResponse
    {
        $request->validate(['stage' => 'required|string']);
        $opp = $this->stageUseCase->execute($id, $request->input('stage'));
        return ResponseFormatter::success($opp, 'Stage updated.');
    }
}
