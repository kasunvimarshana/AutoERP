<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Reporting\Enums\ExportFormat;
use Modules\Reporting\Events\ReportExported;
use Modules\Reporting\Events\ReportGenerated;
use Modules\Reporting\Events\ReportPublished;
use Modules\Reporting\Http\Requests\ExecuteReportRequest;
use Modules\Reporting\Http\Requests\ExportReportRequest;
use Modules\Reporting\Http\Requests\StoreReportRequest;
use Modules\Reporting\Http\Requests\UpdateReportRequest;
use Modules\Reporting\Http\Resources\ReportResource;
use Modules\Reporting\Models\Report;
use Modules\Reporting\Repositories\ReportRepository;
use Modules\Reporting\Services\ReportBuilderService;
use Modules\Reporting\Services\ReportExportService;

class ReportController extends Controller
{
    public function __construct(
        private ReportRepository $reportRepository,
        private ReportBuilderService $reportBuilderService,
        private ReportExportService $exportService
    ) {}

    /**
     * Display a listing of reports
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->type,
            'status' => $request->status,
            'is_template' => $request->boolean('is_template'),
            'is_shared' => $request->boolean('is_shared'),
            'search' => $request->search,
        ];

        $perPage = $request->get('per_page', 15);
        $reports = $this->reportRepository->getAll(array_filter($filters), $perPage);

        return ApiResponse::paginated(
            $reports->setCollection(
                $reports->getCollection()->map(fn ($report) => new ReportResource($report))
            ),
            'Reports retrieved successfully'
        );
    }

    /**
     * Store a newly created report
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['tenant_id'] = $request->user()->tenant_id;
            $data['organization_id'] = $request->user()->organization_id;
            $data['user_id'] = $request->user()->id;

            return $this->reportRepository->create($data);
        });

        return ApiResponse::created(
            new ReportResource($report),
            'Report created successfully'
        );
    }

    /**
     * Display the specified report
     */
    public function show(Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        $report->load(['user', 'schedules', 'executions' => fn ($q) => $q->latest()->limit(5)]);

        return ApiResponse::success(
            new ReportResource($report),
            'Report retrieved successfully'
        );
    }

    /**
     * Update the specified report
     */
    public function update(UpdateReportRequest $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        DB::transaction(function () use ($request, $report) {
            $this->reportRepository->update($report, $request->validated());
        });

        return ApiResponse::success(
            new ReportResource($report->fresh()),
            'Report updated successfully'
        );
    }

    /**
     * Remove the specified report
     */
    public function destroy(Report $report): JsonResponse
    {
        $this->authorize('delete', $report);

        DB::transaction(function () use ($report) {
            $this->reportRepository->delete($report);
        });

        return ApiResponse::success(
            null,
            'Report deleted successfully'
        );
    }

    /**
     * Execute report and return results
     */
    public function execute(ExecuteReportRequest $request, Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        $filters = $request->validated()['filters'] ?? [];

        $result = $this->reportBuilderService->execute(
            $report,
            $filters,
            $request->user()->id
        );

        event(new ReportGenerated(
            $report,
            $filters,
            $result['count'],
            $result['execution_time']
        ));

        return ApiResponse::success([
            'data' => $result['data'],
            'count' => $result['count'],
            'execution_time' => $result['execution_time'],
            'execution_id' => $result['execution_id'],
        ], 'Report executed successfully');
    }

    /**
     * Export report to specified format
     */
    public function export(ExportReportRequest $request, Report $report): mixed
    {
        $this->authorize('view', $report);

        $filters = $request->validated()['filters'] ?? [];
        $format = ExportFormat::from($request->validated()['format']);

        $result = $this->reportBuilderService->execute($report, $filters);
        $data = $result['data']->toArray();

        event(new ReportExported($report, $format, ''));

        // Stream download for better performance
        if ($request->boolean('stream', true)) {
            return match ($format) {
                ExportFormat::CSV => $this->exportService->streamCsv($data, $report->name),
                ExportFormat::JSON => $this->exportService->streamJson($data, $report->name),
                default => throw new \RuntimeException('Unsupported export format'),
            };
        }

        // Store and return path
        $path = $this->exportService->export($data, $format, $report->name);

        return ApiResponse::success([
            'path' => $path,
            'url' => $this->exportService->getDownloadUrl($path),
        ], 'Report exported successfully');
    }

    /**
     * Publish report
     */
    public function publish(Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        DB::transaction(function () use ($report) {
            $this->reportRepository->publish($report);
        });

        event(new ReportPublished($report));

        return ApiResponse::success(
            new ReportResource($report->fresh()),
            'Report published successfully'
        );
    }

    /**
     * Archive report
     */
    public function archive(Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        DB::transaction(function () use ($report) {
            $this->reportRepository->archive($report);
        });

        return ApiResponse::success(
            new ReportResource($report->fresh()),
            'Report archived successfully'
        );
    }

    /**
     * Get report templates
     */
    public function templates(Request $request): JsonResponse
    {
        $templates = $this->reportRepository->getTemplates();

        return ApiResponse::success(
            $templates->map(fn ($report) => new ReportResource($report)),
            'Report templates retrieved successfully'
        );
    }
}
