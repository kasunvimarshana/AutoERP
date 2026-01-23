<?php

namespace App\Modules\CRMManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\CRMManagement\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends BaseController
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'user_id' => $request->input('user_id'),
                'type' => $request->input('type'),
                'status' => $request->input('status'),
                'per_page' => $request->input('per_page', 15),
            ];

            $notifications = $this->notificationService->search($criteria);
            return $this->success($notifications);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'type' => 'required|string|max:100',
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'status' => 'nullable|in:pending,sent,read',
                'scheduled_at' => 'nullable|date',
                'data' => 'nullable|array',
            ]);

            $notification = $this->notificationService->create($request->all());
            return $this->created($notification, 'Notification created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $notification = $this->notificationService->findById($id);
            
            if (!$notification) {
                return $this->notFound('Notification not found');
            }

            return $this->success($notification);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function markAsRead(int $id): JsonResponse
    {
        try {
            $notification = $this->notificationService->markAsRead($id);
            return $this->success($notification, 'Notification marked as read');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
