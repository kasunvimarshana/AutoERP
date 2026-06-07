<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\ReportRunRequest;
use Modules\Reporting\Services\ReportCatalog;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportQueryBuilder;

final class ReportController
{
    public function __construct(
        private readonly ReportCatalog $catalog,
        private readonly ReportQueryBuilder $reports,
        private readonly ReportExport $export,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->catalog->index()]);
    }

    public function show(string $report): JsonResponse
    {
        return response()->json(['data' => $this->catalog->get($report)->toArray()]);
    }

    public function run(ReportRunRequest $request, string $report): JsonResponse
    {
        $definition = $this->catalog->get($report);
        $input = $request->validated();
        $input['filters'] = $request->filters();
        $page = $this->reports->paginate($definition, $request->tenantId(), $request->organizationUnitId(), $input, $request->perPage());

        return response()->json([
            'data' => $this->reports->rows($definition, $page->items()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'from' => $page->firstItem(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'to' => $page->lastItem(),
                'total' => $page->total(),
            ],
            'report' => $definition->toArray(),
        ]);
    }

    public function export(ReportRunRequest $request, string $report, string $format): Response
    {
        $definition = $this->catalog->get($report);
        $input = $request->validated();
        $input['filters'] = $request->filters();
        $rows = $this->reports->rows(
            $definition,
            $this->reports->query($definition, $request->tenantId(), $request->organizationUnitId(), $input)->limit(5000)->get(),
        );

        return match ($format) {
            'csv' => $this->export->csv($definition, $rows),
            'xlsx' => $this->export->xlsx($definition, $rows),
            'pdf' => $this->export->pdf($definition, $rows),
            'print' => $this->export->print($definition, $rows),
            default => response('Unsupported export format.', 422),
        };
    }
}
