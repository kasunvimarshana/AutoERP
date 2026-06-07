<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\TechnicianWorkReportRequest;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\TechnicianWorkReportService;

final class TechnicianWorkReportController
{
    public function __construct(
        private readonly TechnicianWorkReportService $reports,
        private readonly ReportExport $export,
    ) {}

    public function index(TechnicianWorkReportRequest $request): JsonResponse
    {
        $input = $request->validated();
        $input['tenant_id'] = $request->tenantId();
        $input['organization_unit_id'] = $request->organizationUnitId();

        return response()->json($this->reports->run($input));
    }

    public function export(TechnicianWorkReportRequest $request, string $format): Response
    {
        $input = $request->validated();
        $input['tenant_id'] = $request->tenantId();
        $input['organization_unit_id'] = $request->organizationUnitId();
        $definition = $this->reports->definition();
        $rows = $this->reports->exportRows($input)->all();

        return match ($format) {
            'csv' => $this->export->csv($definition, $rows),
            'xlsx' => $this->export->xlsx($definition, $rows),
            'pdf' => $this->export->pdf($definition, $rows),
            'print' => $this->export->print($definition, $rows),
            default => response('Unsupported export format.', 422),
        };
    }
}
