<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Barcode\Application\Contracts\ManageLabelTemplateServiceInterface;

class LabelTemplateController extends Controller
{
    public function __construct(
        private readonly ManageLabelTemplateServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $active   = $request->boolean('active', false);

        $templates = $active
            ? $this->service->listActive($tenantId)
            : $this->service->listAll($tenantId);

        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $template = $this->service->create(
            (int) $request->get('tenant_id', 0),
            (string) $request->get('name', ''),
            (string) $request->get('format', 'zpl'),
            (string) $request->get('content', ''),
            (array)  $request->get('default_variables', []),
        );

        return response()->json($template, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->getById($id));
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $template = $this->service->updateContent(
            $id,
            (string) $request->get('content', ''),
            (string) $request->get('format', 'zpl'),
        );

        return response()->json($template);
    }

    public function activate(int $id): JsonResponse
    {
        return response()->json($this->service->activate($id));
    }

    public function deactivate(int $id): JsonResponse
    {
        return response()->json($this->service->deactivate($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
