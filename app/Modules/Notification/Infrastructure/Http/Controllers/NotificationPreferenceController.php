<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Application\Contracts\NotificationPreferenceServiceInterface;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $userId   = (int) $request->get('user_id', 0);
        return response()->json($this->service->listForUser($tenantId, $userId));
    }

    public function upsert(Request $request): JsonResponse
    {
        $preference = $this->service->setPreference(
            (int)    $request->input('tenant_id', 0),
            (int)    $request->input('user_id', 0),
            (string) $request->input('notification_type'),
            (string) $request->input('channel'),
            (bool)   $request->input('enabled', true),
        );

        return response()->json($preference, 200);
    }

    public function check(Request $request): JsonResponse
    {
        $enabled = $this->service->isEnabled(
            (int)    $request->get('tenant_id', 0),
            (int)    $request->get('user_id', 0),
            (string) $request->get('notification_type'),
            (string) $request->get('channel'),
        );

        return response()->json(['enabled' => $enabled]);
    }
}
