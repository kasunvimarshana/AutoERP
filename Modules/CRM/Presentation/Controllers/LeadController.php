<?php
namespace Modules\CRM\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\CRM\Application\UseCases\CreateLeadUseCase;
use Modules\CRM\Application\UseCases\ConvertLeadUseCase;
use Modules\CRM\Infrastructure\Repositories\LeadRepository;
use Modules\CRM\Presentation\Requests\StoreLeadRequest;
use Modules\Shared\Application\ResponseFormatter;
class LeadController extends Controller
{
    public function __construct(
        private CreateLeadUseCase $createUseCase,
        private ConvertLeadUseCase $convertUseCase,
        private LeadRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($lead, 'Lead created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $lead = $this->repo->findById($id);
        if (!$lead) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($lead);
    }
    public function update(StoreLeadRequest $request, string $id): JsonResponse
    {
        $lead = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($lead, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
    public function convert(string $id): JsonResponse
    {
        $opportunity = $this->convertUseCase->execute(['lead_id' => $id]);
        return ResponseFormatter::success($opportunity, 'Lead converted to opportunity.');
    }
}
