<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Barcode\Application\Contracts\PrintBarcodeLabelServiceInterface;

class BarcodePrintJobController extends Controller
{
    public function __construct(
        private readonly PrintBarcodeLabelServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $status   = $request->get('status');

        $jobs = $status
            ? $this->service->listByStatus($tenantId, (string) $status)
            : $this->service->listAll($tenantId);

        return response()->json($jobs);
    }

    public function store(Request $request): JsonResponse
    {
        $job = $this->service->queue(
            (int) $request->get('tenant_id', 0),
            (int) $request->get('barcode_definition_id', 0),
            $request->has('label_template_id') ? (int) $request->get('label_template_id') : null,
            (string) $request->get('format', 'zpl'),
            $request->get('printer_target'),
            (int) $request->get('copies', 1),
            (array) $request->get('variables', []),
        );

        return response()->json($job, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->getById($id));
    }

    public function process(int $id): JsonResponse
    {
        return response()->json($this->service->process($id));
    }

    public function cancel(int $id): JsonResponse
    {
        return response()->json($this->service->cancel($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
