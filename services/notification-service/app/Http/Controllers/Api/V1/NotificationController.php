<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;
use App\Contracts\Services\NotificationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Http\Requests\Notification\RegisterWebhookRequest;
use Illuminate\Http\JsonResponse;
class NotificationController extends Controller {
    public function __construct(private readonly NotificationServiceInterface $notificationService) {}
    public function send(SendNotificationRequest $request): JsonResponse {
        $success = $this->notificationService->send($request->validated());
        return response()->json(['success' => $success, 'message' => $success ? 'Notification sent.' : 'Notification failed.'], $success ? 200 : 500);
    }
    public function registerWebhook(RegisterWebhookRequest $request): JsonResponse {
        $data = $request->validated();
        $data['tenant_id'] = $request->input('_tenant_id');
        $webhook = $this->notificationService->registerWebhook($data);
        return response()->json(['success' => true, 'data' => ['id' => $webhook->id, 'name' => $webhook->name, 'url' => $webhook->url, 'events' => $webhook->events, 'is_active' => $webhook->is_active]], 201);
    }
}
