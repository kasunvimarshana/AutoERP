<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Barcode\Application\Contracts\RecordBarcodeScanServiceInterface;

class BarcodeScanController extends Controller
{
    public function __construct(
        private readonly RecordBarcodeScanServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);

        if ($request->has('barcode_definition_id')) {
            return response()->json(
                $this->service->getByDefinition($tenantId, (int) $request->get('barcode_definition_id'))
            );
        }

        if ($request->has('from') && $request->has('to')) {
            $from = new \DateTime((string) $request->get('from'));
            $to   = new \DateTime((string) $request->get('to'));
            return response()->json($this->service->getByDateRange($tenantId, $from, $to));
        }

        return response()->json([]);
    }

    public function store(Request $request): JsonResponse
    {
        $scan = $this->service->record(
            (int) $request->get('tenant_id', 0),
            (string) $request->get('scanned_value', ''),
            $request->has('scanned_by_user_id') ? (int) $request->get('scanned_by_user_id') : null,
            $request->get('device_id'),
            $request->get('location_tag'),
            (array) $request->get('metadata', []),
        );

        return response()->json($scan, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->getById($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
