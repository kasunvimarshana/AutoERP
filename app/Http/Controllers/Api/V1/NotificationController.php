<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function indexTemplates(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($this->notificationService->paginateTemplates($tenantId, $perPage));
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('notifications.create'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'channel' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        $template = $this->notificationService->createTemplate($data);

        return response()->json($template, 201);
    }

    public function updateTemplate(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('notifications.update'), 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'channel' => 'sometimes|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'sometimes|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        $template = $this->notificationService->updateTemplate($id, $data);

        return response()->json($template);
    }

    public function send(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('notifications.send'), 403);

        $data = $request->validate([
            'template_slug' => 'required|string|max:255',
            'notifiable_type' => 'required|string|max:255',
            'notifiable_id' => 'required|string|max:255',
            'variables' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $log = $this->notificationService->send(
            $request->user()->tenant_id,
            $data['template_slug'],
            $data['notifiable_type'],
            $data['notifiable_id'],
            $data['variables'] ?? [],
            $data['metadata'] ?? []
        );

        return response()->json($log, 201);
    }

    public function indexLogs(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['channel', 'status', 'notifiable_type', 'date_from', 'date_to']);

        return response()->json($this->notificationService->paginateLogs($tenantId, $filters, $perPage));
    }
}
