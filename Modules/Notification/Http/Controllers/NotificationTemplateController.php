<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Notification\Http\Requests\StoreTemplateRequest;
use Modules\Notification\Http\Requests\UpdateTemplateRequest;
use Modules\Notification\Http\Resources\NotificationTemplateResource;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Repositories\NotificationTemplateRepository;

class NotificationTemplateController extends Controller
{
    public function __construct(
        private NotificationTemplateRepository $templateRepository
    ) {}

    /**
     * Display a listing of notification templates.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NotificationTemplate::class);

        $query = NotificationTemplate::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_system')) {
            $query->where('is_system', $request->boolean('is_system'));
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $templates = $query->latest()->paginate($perPage);

        return ApiResponse::paginated(
            $templates->setCollection(
                $templates->getCollection()->map(fn ($template) => new NotificationTemplateResource($template))
            ),
            'Templates retrieved successfully'
        );
    }

    /**
     * Store a newly created notification template.
     */
    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;

        $template = DB::transaction(function () use ($data) {
            return $this->templateRepository->create($data);
        });

        return ApiResponse::created(
            new NotificationTemplateResource($template),
            'Template created successfully'
        );
    }

    /**
     * Display the specified notification template.
     */
    public function show(NotificationTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $template->load('notifications');

        return ApiResponse::success(
            new NotificationTemplateResource($template),
            'Template retrieved successfully'
        );
    }

    /**
     * Update the specified notification template.
     */
    public function update(UpdateTemplateRequest $request, NotificationTemplate $template): JsonResponse
    {
        $data = $request->validated();

        $template = DB::transaction(function () use ($template, $data) {
            return $this->templateRepository->update($template->id, $data);
        });

        return ApiResponse::success(
            new NotificationTemplateResource($template),
            'Template updated successfully'
        );
    }

    /**
     * Remove the specified notification template.
     */
    public function destroy(NotificationTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);

        if ($template->is_system) {
            return ApiResponse::error(
                'System templates cannot be deleted',
                403
            );
        }

        DB::transaction(function () use ($template) {
            $this->templateRepository->delete($template->id);
        });

        return ApiResponse::success(
            null,
            'Template deleted successfully'
        );
    }

    /**
     * Preview template rendering with provided data.
     */
    public function preview(Request $request, NotificationTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $request->validate([
            'data' => ['nullable', 'array'],
        ]);

        $data = $request->get('data', []);
        $rendered = $template->render($data);

        return ApiResponse::success(
            [
                'template' => new NotificationTemplateResource($template),
                'rendered' => $rendered,
            ],
            'Template preview generated successfully'
        );
    }
}
