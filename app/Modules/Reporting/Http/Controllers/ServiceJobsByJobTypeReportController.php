<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\ServiceJobsByJobTypeReportRequest;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\Reporting\Services\ServiceJobsByJobTypeReportService;

final class ServiceJobsByJobTypeReportController
{
    public function __construct(
        private readonly ServiceJobsByJobTypeReportService $reports,
        private readonly ReportExport $export,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(ServiceJobsByJobTypeReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        return response()->json($this->reports->run($this->input($request)));
    }

    public function export(ServiceJobsByJobTypeReportRequest $request, string $format): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_EXPORT);
        $input = $this->input($request);

        return $this->export->export(
            $format,
            $this->reports->definition(),
            $this->reports->exportRows($input)->all(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $input
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function input(ServiceJobsByJobTypeReportRequest $request): array
    {
        return [
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ];
    }
}
