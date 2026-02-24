<?php

namespace Modules\ProjectManagement\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ProjectManagement\Application\UseCases\LogTimeUseCase;
use Modules\ProjectManagement\Infrastructure\Repositories\TimeEntryRepository;
use Modules\ProjectManagement\Presentation\Requests\StoreTimeEntryRequest;
use Modules\Shared\Application\ResponseFormatter;

class TimeEntryController extends Controller
{
    public function __construct(
        private LogTimeUseCase      $logTimeUseCase,
        private TimeEntryRepository $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreTimeEntryRequest $request): JsonResponse
    {
        $entry = $this->logTimeUseCase->execute($request->validated());
        return ResponseFormatter::success($entry, 'Time entry logged.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $entry = $this->repo->findById($id);
        if (! $entry) {
            return ResponseFormatter::error('Time entry not found.', [], 404);
        }
        return ResponseFormatter::success($entry);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Time entry deleted.');
    }
}
