<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Analytics\Application\Contracts\AnalyticsServiceInterface;
use Modules\Analytics\Application\DTOs\CreateAnalyticsSnapshotDTO;
use Modules\Analytics\Domain\Exceptions\AnalyticsSnapshotNotFoundException;
use Modules\Analytics\Infrastructure\Http\Requests\CreateAnalyticsSnapshotRequest;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsServiceInterface $service,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $orgUnitId = $request->query('org_unit_id') !== null ? (int) $request->query('org_unit_id') : null;

        return response()->json($this->service->getSummary($tenantId, $orgUnitId));
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $filters = $request->only(['summary_date', 'org_unit_id']);

        return response()->json($this->service->listSnapshots($tenantId, $filters));
    }

    public function store(CreateAnalyticsSnapshotRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $data = $request->validated();

        $dto = new CreateAnalyticsSnapshotDTO(
            tenantId: $tenantId,
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            summaryDate: $data['summary_date'],
            metadata: $data['metadata'] ?? null,
        );

        return response()->json($this->service->createSnapshot($dto), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');

        try {
            return response()->json($this->service->getSnapshotById($id, $tenantId));
        } catch (AnalyticsSnapshotNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');

        try {
            $this->service->deleteSnapshot($id, $tenantId);

            return response()->json(null, 204);
        } catch (AnalyticsSnapshotNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
