<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reporting\Http\Requests\EmployeeCommissionReportRequest;
use Modules\Reporting\Services\EmployeeCommissionReportService;
use Modules\Reporting\Services\ReportExport;

final class EmployeeCommissionReportController
{
    public function __construct(
        private readonly EmployeeCommissionReportService $reports,
        private readonly ReportExport $export,
    ) {}

    public function index(EmployeeCommissionReportRequest $request): JsonResponse
    {
        return response()->json($this->reports->run($this->input($request)));
    }

    public function export(EmployeeCommissionReportRequest $request, string $format): Response
    {
        $input = $this->input($request);
        $rows = $this->reports->exportRows($input)->all();
        $definition = $this->reports->definition();

        return $this->export->export(
            $format,
            $definition,
            $rows,
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
