<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\ReportRunRequest;
use Modules\Reporting\Services\ReportService;

final class ReportController
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->reports->index()]);
    }

    public function show(string $report): JsonResponse
    {
        return response()->json(['data' => $this->reports->definition($report)]);
    }

    public function run(ReportRunRequest $request, string $report): JsonResponse
    {
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
}
