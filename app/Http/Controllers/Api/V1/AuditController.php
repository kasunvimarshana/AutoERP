<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('audit.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        $query = AuditLog::where('tenant_id', $tenantId);

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->query('auditable_type'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<', \Carbon\Carbon::parse($request->query('date_to'))->addDay()->startOfDay());
        }

        return response()->json($query->latest()->paginate($perPage));
    }
}
