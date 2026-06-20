<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\ReportRunRequest;
use Modules\Reporting\Services\ReportService;
use Modules\Reporting\Services\ReportingAuthorizationService;

final class ReportController
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(ReportRunRequest $request): JsonResponse
    {
        $this->assertView($request);

        return response()->json(['data' => $this->reports->index()]);
    }

    public function show(ReportRunRequest $request, string $report): JsonResponse
    {
        $this->assertView($request);

        return response()->json(['data' => $this->reports->definition($report)]);
    }

    public function run(ReportRunRequest $request, string $report): JsonResponse
    {
        $this->assertView($request);
        $input = $request->validated();
        $input['filters'] = $request->filters();

        return response()->json($this->reports->run(
            $report,
            $request->tenantId(),
            $request->organizationUnitId(),
            $input,
            $request->perPage(),
        ));
    }

    public function export(ReportRunRequest $request, string $report, string $format): Response
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_EXPORT,
        );
        $input = $request->validated();
        $input['filters'] = $request->filters();

        return $this->reports->export(
            $report,
            $format,
            $request->tenantId(),
            $request->organizationUnitId(),
            $input,
        );
    }

    private function assertView(ReportRunRequest $request): void
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_VIEW,
        );
    }
}
