<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\PaymentMethodWiseSummaryReportRequest;
use Modules\Reporting\Services\PaymentMethodWiseSummaryReportService;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;

final class PaymentMethodWiseSummaryReportController
{
    public function __construct(
        private readonly PaymentMethodWiseSummaryReportService $reports,
        private readonly ReportExport $exports,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function index(PaymentMethodWiseSummaryReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        return response()->json(['data' => $this->result($request)]);
    }

    public function export(PaymentMethodWiseSummaryReportRequest $request, string $format): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_EXPORT);
        $result = $this->result($request);

        return $this->exports->export(
            $format,
            $this->reports->definition($result),
            $this->reports->exportRows($result),
            $request->tenantId(),
            $request->organizationUnitId(),
            [
                'date_from' => $request->validated('date_from'),
                'date_to' => $request->validated('date_to'),
                'source' => $request->validated('source') ?? PaymentMethodWiseSummaryReportService::SOURCE_ALL,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function result(PaymentMethodWiseSummaryReportRequest $request): array
    {
        return $this->reports->run(
            $request->tenantId(),
            $request->organizationUnitId(),
            (string) $request->validated('date_from'),
            (string) $request->validated('date_to'),
            (string) ($request->validated('source') ?? PaymentMethodWiseSummaryReportService::SOURCE_ALL),
        );
    }
}
