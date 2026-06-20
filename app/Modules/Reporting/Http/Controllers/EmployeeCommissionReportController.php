<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\EmployeeCommissionReportRequest;
use Modules\Reporting\Services\EmployeeCommissionReportService;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;

final class EmployeeCommissionReportController
{
    public function __construct(
        private readonly EmployeeCommissionReportService $reports,
        private readonly ReportExport $export,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(EmployeeCommissionReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        return response()->json($this->reports->run($this->input($request)));
    }

    public function export(EmployeeCommissionReportRequest $request, string $format): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_EXPORT);
        $input = $this->input($request);

        return $this->export->export(
            $format,
            $this->reports->definition(),
            $this->reports->exportRows($input)->all(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $input,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function input(EmployeeCommissionReportRequest $request): array
    {
        return [
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ];
    }
}
