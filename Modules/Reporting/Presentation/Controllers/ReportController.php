<?php

namespace Modules\Reporting\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Reporting\Application\UseCases\SaveReportUseCase;
use Modules\Reporting\Domain\Contracts\ReportRepositoryInterface;
use Modules\Reporting\Presentation\Requests\StoreReportRequest;

class ReportController extends Controller
{
    public function __construct(
        private ReportRepositoryInterface $reportRepo,
        private SaveReportUseCase         $saveUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->reportRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->saveUseCase->execute(array_merge(
            $request->validated(),
            [
                'tenant_id' => auth()->user()?->tenant_id,
                'user_id'   => auth()->id(),
            ]
        ));

        return response()->json($report, 201);
    }

    public function show(string $id): JsonResponse
    {
        $report = $this->reportRepo->findById($id);

        if (! $report) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($report);
    }

    public function update(StoreReportRequest $request, string $id): JsonResponse
    {
        $report = $this->reportRepo->update($id, $request->validated());

        return response()->json($report);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->reportRepo->delete($id);

        return response()->json(null, 204);
    }
}
