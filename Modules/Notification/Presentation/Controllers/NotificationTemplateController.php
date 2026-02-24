<?php
namespace Modules\Notification\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Infrastructure\Models\NotificationTemplateModel;
use Modules\Shared\Application\ResponseFormatter;
class NotificationTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = NotificationTemplateModel::paginate(20);
        return ResponseFormatter::paginated($templates);
    }
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'event_type' => ['required', 'string'],
            'channel' => ['required', 'string'],
            'subject' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $template = NotificationTemplateModel::create(array_merge(
            $request->validated(),
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'tenant_id' => app('current.tenant.id')]
        ));
        return ResponseFormatter::success($template, 'Template created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $template = NotificationTemplateModel::find($id);
        if (! $template) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($template);
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'subject' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $template = NotificationTemplateModel::findOrFail($id);
        $template->update($request->validated());
        return ResponseFormatter::success($template->fresh(), 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        NotificationTemplateModel::findOrFail($id)->delete();
        return ResponseFormatter::success(null, 'Deleted.');
    }
}
