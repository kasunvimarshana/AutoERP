<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\TechnicianWorkReportRequest;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\Reporting\Services\TechnicianWorkReportService;

final class TechnicianWorkReportController
{
    public function __construct(
        private readonly TechnicianWorkReportService $reports,
        private readonly ReportExport $export,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(TechnicianWorkReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);
        $input = $request->validated();
        $input['tenant_id'] = $request->tenantId();
        $input['organization_unit_id'] = $request->organizationUnitId();

        return response()->json($this->reports->run($input));
    }

    public function export(TechnicianWorkReportRequest $request, string $format): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_EXPORT);
        $input = $request->validated();
        $input['tenant_id'] = $request->tenantId();
        $input['organization_unit_id'] = $request->organizationUnitId();

        return $this->export->export(
            $format,
            $this->reports->definition(),
            $this->reports->exportRows($input)->all(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $input,
        );
    }
}
