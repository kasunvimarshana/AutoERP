<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Application\Contracts\NotificationTemplateServiceInterface;

class NotificationTemplateController extends Controller
{
    public function __construct(
        private readonly NotificationTemplateServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        return response()->json($this->service->listByTenant($tenantId));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->getById($id));
    }

    public function store(Request $request): JsonResponse
    {
        $template = $this->service->create($request->all());
        return response()->json($template, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json($this->service->update($id, $request->all()));
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
