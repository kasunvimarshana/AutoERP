<?php

declare(strict_types=1);

namespace Modules\Reporting\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Reporting\Application\DTOs\GenerateReportDTO;
use Modules\Reporting\Application\Services\ReportingService;

/**
 * @OA\Tag(name="Reporting", description="Reporting and analytics endpoints")
 */
class ReportingController extends Controller
{
    public function __construct(private readonly ReportingService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/reporting/definitions",
     *     tags={"Reporting"},
     *     summary="List report definitions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of report definitions")
     * )
     */
    public function index(): JsonResponse
    {
        return ApiResponse::ok($this->service->listDefinitions());
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reporting/definitions",
     *     tags={"Reporting"},
     *     summary="Create a report definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug","type"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="type", type="string", enum={"financial","inventory","aging","tax","sales","procurement","custom"}),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="filters", type="object", nullable=true),
     *             @OA\Property(property="columns", type="array", nullable=true, @OA\Items(type="string")),
     *             @OA\Property(property="sort_config", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Report definition created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255'],
            'type'        => ['required', 'string', 'in:financial,inventory,aging,tax,sales,procurement,custom'],
            'description' => ['nullable', 'string'],
            'filters'     => ['nullable', 'array'],
            'columns'     => ['nullable', 'array'],
            'sort_config' => ['nullable', 'array'],
        ]);

        return ApiResponse::created($this->service->createDefinition($validated), 'Report definition created.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reporting/generate",
     *     tags={"Reporting"},
     *     summary="Generate (queue) a report export",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"report_definition_id","export_format"},
     *             @OA\Property(property="report_definition_id", type="integer"),
     *             @OA\Property(property="export_format", type="string", enum={"csv","pdf"}),
     *             @OA\Property(property="filters", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Export queued"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_definition_id' => ['required', 'integer'],
            'export_format'        => ['required', 'string', 'in:csv,pdf'],
            'filters'              => ['nullable', 'array'],
        ]);

        $dto = GenerateReportDTO::fromArray($validated);

        return ApiResponse::created($this->service->generateReport($dto), 'Report export queued.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reporting/schedules",
     *     tags={"Reporting"},
     *     summary="Schedule a report",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"report_definition_id","frequency","export_format"},
     *             @OA\Property(property="report_definition_id", type="integer"),
     *             @OA\Property(property="frequency", type="string", enum={"daily","weekly","monthly"}),
     *             @OA\Property(property="export_format", type="string", enum={"csv","pdf"}),
     *             @OA\Property(property="recipients", type="array", nullable=true, @OA\Items(type="string")),
     *             @OA\Property(property="next_run_at", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Report scheduled"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function schedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_definition_id' => ['required', 'integer'],
            'frequency'            => ['required', 'string', 'in:daily,weekly,monthly'],
            'export_format'        => ['required', 'string', 'in:csv,pdf'],
            'recipients'           => ['nullable', 'array'],
            'next_run_at'          => ['nullable', 'date'],
        ]);

        return ApiResponse::created($this->service->scheduleReport($validated), 'Report scheduled.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/reporting/definitions/{id}",
     *     tags={"Reporting"},
     *     summary="Update a report definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="type", type="string", enum={"financial","inventory","aging","tax","sales","procurement","custom"}),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="filters", type="object", nullable=true),
     *             @OA\Property(property="columns", type="array", nullable=true, @OA\Items(type="string")),
     *             @OA\Property(property="sort_config", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Report definition updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateDefinition(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'slug'        => ['sometimes', 'string', 'max:255'],
            'type'        => ['sometimes', 'string', 'in:financial,inventory,aging,tax,sales,procurement,custom'],
            'description' => ['nullable', 'string'],
            'filters'     => ['nullable', 'array'],
            'columns'     => ['nullable', 'array'],
            'sort_config' => ['nullable', 'array'],
        ]);

        return ApiResponse::ok($this->service->updateDefinition($id, $validated));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/reporting/definitions/{id}",
     *     tags={"Reporting"},
     *     summary="Delete a report definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Report definition deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deleteDefinition(int $id): JsonResponse
    {
        $this->service->deleteDefinition($id);

        return ApiResponse::ok(null, 'Report definition deleted.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reporting/schedules",
     *     tags={"Reporting"},
     *     summary="List report schedules",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of report schedules")
     * )
     */
    public function listSchedules(): JsonResponse
    {
        return ApiResponse::ok($this->service->listSchedules());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reporting/exports",
     *     tags={"Reporting"},
     *     summary="List report exports",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of report exports")
     * )
     */
    public function listExports(): JsonResponse
    {
        return ApiResponse::ok($this->service->listExports());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reporting/exports/{id}",
     *     tags={"Reporting"},
     *     summary="Show a report export",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Report export details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showExport(int $id): JsonResponse
    {
        return ApiResponse::ok($this->service->showExport($id));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reporting/schedules/{id}",
     *     tags={"Reporting"},
     *     summary="Show a single report schedule",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Report schedule details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showSchedule(int $id): JsonResponse
    {
        return ApiResponse::ok($this->service->showSchedule($id));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/reporting/schedules/{id}",
     *     tags={"Reporting"},
     *     summary="Update a report schedule",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="frequency", type="string", enum={"daily","weekly","monthly"}),
     *             @OA\Property(property="export_format", type="string", enum={"csv","pdf"}),
     *             @OA\Property(property="recipients", type="array", nullable=true, @OA\Items(type="string")),
     *             @OA\Property(property="next_run_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Report schedule updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateSchedule(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'frequency'     => ['sometimes', 'string', 'in:daily,weekly,monthly'],
            'export_format' => ['sometimes', 'string', 'in:csv,pdf'],
            'recipients'    => ['nullable', 'array'],
            'next_run_at'   => ['nullable', 'date'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        return ApiResponse::ok($this->service->updateSchedule($id, $validated));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/reporting/schedules/{id}",
     *     tags={"Reporting"},
     *     summary="Delete a report schedule",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Report schedule deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deleteSchedule(int $id): JsonResponse
    {
        $this->service->deleteSchedule($id);

        return ApiResponse::ok(null, 'Report schedule deleted.');
    }
}
