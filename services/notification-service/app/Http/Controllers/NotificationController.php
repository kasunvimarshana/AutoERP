<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    public function index(Request $request)
    {
        $query = Notification::query()
            ->when($request->tenant_id, fn($q,$v) => $q->byTenant($v))
            ->when($request->type,      fn($q,$v) => $q->byType($v))
            ->when($request->status,    fn($q,$v) => $q->where('status',$v))
            ->when($request->channel,   fn($q,$v) => $q->byChannel($v))
            ->latest();

        return response()->json($query->paginate(20));
    }

    public function show($id)
    {
        return response()->json(Notification::findOrFail($id));
    }

    public function retry($id)
    {
        $notification = Notification::findOrFail($id);
        if ($notification->status !== 'failed') {
            return response()->json(['error' => 'Only failed notifications can be retried'], 422);
        }
        $sent = $this->service->sendNotification($notification);
        return response()->json(['success' => $sent, 'status' => $notification->fresh()->status]);
    }
}
