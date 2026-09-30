<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Reporting\Http\Requests\DashboardSummaryRequest;
use Modules\Reporting\Services\DashboardSummaryService;
use Modules\Reporting\Services\ReportingAuthorizationService;

final class DashboardController
{
    public function __construct(
        private readonly DashboardSummaryService $dashboard,
        private readonly ReportingAuthorizationService $authorization,
    ) {}

    public function __invoke(DashboardSummaryRequest $request): JsonResponse
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            ReportingAuthorizationService::REPORTS_VIEW,
        );
        $validated = $request->validated();

        return response()->json([
            'data' => $this->dashboard->run(
                $request->tenantId(),
                $request->organizationUnitId(),
                (string) $validated['date_from'],
                (string) $validated['date_to'],
            ),
        ]);
    }
}
