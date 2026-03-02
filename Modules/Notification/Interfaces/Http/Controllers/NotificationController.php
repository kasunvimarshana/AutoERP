<?php

declare(strict_types=1);

namespace Modules\Notification\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Notification\Application\DTOs\SendNotificationDTO;
use Modules\Notification\Application\Services\NotificationService;

/**
 * Notification controller.
 *
 * Input validation and response formatting ONLY.
 * All business logic is delegated to NotificationService.
 *
 * @OA\Tag(name="Notification", description="Notification template management and dispatch endpoints")
 */
class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/notification/templates",
     *     tags={"Notification"},
     *     summary="List all notification templates",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of notification templates",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $templates = $this->service->listTemplates();

        return ApiResponse::success($templates, 'Notification templates retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notification/templates",
     *     tags={"Notification"},
     *     summary="Create a new notification template",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug","channel","body_template"},
     *             @OA\Property(property="name", type="string", example="Welcome Email"),
     *             @OA\Property(property="slug", type="string", example="welcome-email"),
     *             @OA\Property(property="channel", type="string", enum={"email","sms","push","in_app"}),
     *             @OA\Property(property="subject", type="string", nullable=true, example="Welcome to {{ app_name }}"),
     *             @OA\Property(property="body_template", type="string", example="Hello {{ name }}, welcome!"),
     *             @OA\Property(property="variables", type="array", nullable=true, @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Notification template created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['required', 'string', 'max:255'],
            'channel'       => ['required', 'string', 'in:email,sms,push,in_app'],
            'subject'       => ['nullable', 'string', 'max:255'],
            'body_template' => ['required', 'string'],
            'variables'     => ['nullable', 'array'],
        ]);

        $template = $this->service->createTemplate($validated);

        return ApiResponse::created($template, 'Notification template created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/notification/templates/{id}",
     *     tags={"Notification"},
     *     summary="Show a single notification template",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification template"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $template = $this->service->showTemplate($id);

        return ApiResponse::success($template, 'Notification template retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/notification/templates/{id}",
     *     tags={"Notification"},
     *     summary="Update a notification template",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="channel", type="string", enum={"email","sms","push","in_app"}),
     *             @OA\Property(property="subject", type="string", nullable=true),
     *             @OA\Property(property="body_template", type="string"),
     *             @OA\Property(property="variables", type="array", nullable=true, @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Notification template updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateTemplate(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'slug'          => ['sometimes', 'string', 'max:255'],
            'channel'       => ['sometimes', 'string', 'in:email,sms,push,in_app'],
            'subject'       => ['nullable', 'string', 'max:255'],
            'body_template' => ['sometimes', 'string'],
            'variables'     => ['nullable', 'array'],
        ]);

        $template = $this->service->updateTemplate($id, $validated);

        return ApiResponse::success($template, 'Notification template updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/notification/templates/{id}",
     *     tags={"Notification"},
     *     summary="Delete a notification template",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification template deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteTemplate($id);

        return ApiResponse::success(message: 'Notification template deleted.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/notification/logs",
     *     tags={"Notification"},
     *     summary="List notification logs (paginated)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of notification logs",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listLogs(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $logs    = $this->service->listLogs($perPage);

        return ApiResponse::success($logs, 'Notification logs retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notification/send",
     *     tags={"Notification"},
     *     summary="Send a notification",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"channel","recipient"},
     *             @OA\Property(property="template_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="channel", type="string", enum={"email","sms","push","in_app"}),
     *             @OA\Property(property="recipient", type="string", example="user@example.com"),
     *             @OA\Property(property="variables", type="object", nullable=true, example={"name":"John"}),
     *             @OA\Property(property="metadata", type="object", nullable=true, example={"order_id":42})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Notification dispatched"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => ['nullable', 'integer'],
            'channel'     => ['required', 'string', 'in:email,sms,push,in_app'],
            'recipient'   => ['required', 'string', 'max:255'],
            'variables'   => ['nullable', 'array'],
            'metadata'    => ['nullable', 'array'],
        ]);

        $dto = SendNotificationDTO::fromArray($validated);
        $log = $this->service->sendNotification($dto);

        return ApiResponse::created($log, 'Notification dispatched.');
    }
}
