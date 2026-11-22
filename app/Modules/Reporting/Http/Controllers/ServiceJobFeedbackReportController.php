<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\ServiceJobFeedbackReportRequest;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\Reporting\Services\ServiceJobFeedbackReportService;

final class ServiceJobFeedbackReportController
{
    public function __construct(
        private readonly ServiceJobFeedbackReportService $reports,
        private readonly ReportExport $export,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(ServiceJobFeedbackReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        return response()->json($this->reports->run($this->input($request)));
    }

    public function export(ServiceJobFeedbackReportRequest $request, string $format): Response
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
    private function input(ServiceJobFeedbackReportRequest $request): array
    {
        return [
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ];
    }
}
