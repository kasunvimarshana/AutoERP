<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Barcode\Application\Contracts\GenerateBarcodeServiceInterface;
use Modules\Barcode\Application\Contracts\ManageBarcodeServiceInterface;

class BarcodeDefinitionController extends Controller
{
    public function __construct(
        private readonly ManageBarcodeServiceInterface  $manageService,
        private readonly GenerateBarcodeServiceInterface $generateService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);

        return response()->json($this->manageService->listAll($tenantId));
    }

    public function store(Request $request): JsonResponse
    {
        $definition = $this->manageService->create(
            (int) $request->get('tenant_id', 0),
            (string) $request->get('type', ''),
            (string) $request->get('value', ''),
            $request->get('label'),
            $request->get('entity_type'),
            $request->has('entity_id') ? (int) $request->get('entity_id') : null,
            (array) $request->get('metadata', []),
        );

        return response()->json($definition, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->manageService->getById($id));
    }

    public function generate(int $id, Request $request): JsonResponse
    {
        $definition = $this->manageService->getById($id);
        $format     = (string) $request->get('format', 'svg');
        $barcode    = $this->generateService->generate($definition, $format, []);

        return response()->json(['barcode' => $barcode]);
    }

    public function activate(int $id): JsonResponse
    {
        return response()->json($this->manageService->activate($id));
    }

    public function deactivate(int $id): JsonResponse
    {
        return response()->json($this->manageService->deactivate($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->manageService->delete($id);
        return response()->json(null, 204);
    }
}
