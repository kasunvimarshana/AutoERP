<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Item\Models\Item;
use Modules\Reporting\Enums\VehicleServiceSalesSummaryExportSection;
use Modules\Reporting\Http\Requests\VehicleServiceSalesSummaryReportRequest;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\Reporting\Services\VehicleServiceSalesSummaryExport;
use Modules\Reporting\Services\VehicleServiceSalesSummaryReportService;

final class VehicleServiceSalesSummaryReportController
{
    public function __construct(
        private readonly VehicleServiceSalesSummaryReportService $reports,
        private readonly ReportingAuthorizationService $authorization,
        private readonly VehicleServiceSalesSummaryExport $document,
        private readonly ReportExport $exports,
    ) {}

    public function index(VehicleServiceSalesSummaryReportRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_VIEW);

        return response()->json($this->reports->run([
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ]));
    }

    public function export(VehicleServiceSalesSummaryReportRequest $request, string $format): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ReportingAuthorizationService::REPORTS_EXPORT);
        $input = [
            ...$request->validated(),
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
        ];
        $result = $this->reports->run($input);
        $section = VehicleServiceSalesSummaryExportSection::from($request->validated('export_section') ?? VehicleServiceSalesSummaryExportSection::Full->value);
        unset($input['item_id'], $input['export_section']);
        if ($request->validated('item_id') !== null) {
            $item = Item::query()
                ->where('tenant_id', $request->tenantId())
                ->find($request->validated('item_id'));
            $input['item_used'] = $item ? "{$item->code} — {$item->name}" : 'Item unavailable';
        }

        return $this->exports->export(
            $format,
            $this->document->definition($section),
            $this->document->rows($result, $section),
            $request->tenantId(),
            $request->organizationUnitId(),
            $input,
        );
    }
}
