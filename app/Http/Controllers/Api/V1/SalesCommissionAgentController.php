<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sales Commission Agent Controller
 *
 * Manages sales representatives and their commission reports.
 * Commission agents are users with the sales_commission_agent flag.
 */
class SalesCommissionAgentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('users.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        $agents = \App\Models\User::where('tenant_id', $tenantId)
            ->where('is_sales_commission_agent', true)
            ->whereNull('deleted_at')
            ->select(['id', 'name', 'email', 'commission_rate', 'is_active'])
            ->paginate($perPage);

        return response()->json($agents);
    }

    public function totalSell(Request $request, string $userId): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
        ]);

        $tenantId = $request->user()->tenant_id;

        // Sum POS transactions where the user created the sale
        $posTotal = DB::table('pos_transactions')
            ->where('tenant_id', $tenantId)
            ->where('created_by', $userId)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $data['date_from'])
            ->whereDate('created_at', '<=', $data['date_to'])
            ->selectRaw('count(*) as count, sum(total) as total_amount')
            ->first();

        // Sum order sales where the user created the order
        $orderTotal = DB::table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_by', $userId)
            ->whereDate('created_at', '>=', $data['date_from'])
            ->whereDate('created_at', '<=', $data['date_to'])
            ->selectRaw('count(*) as count, sum(total_amount) as total_amount')
            ->first();

        return response()->json([
            'user_id' => $userId,
            'period' => ['from' => $data['date_from'], 'to' => $data['date_to']],
            'pos_sales' => [
                'count' => (int) ($posTotal->count ?? 0),
                'total_amount' => number_format((float) ($posTotal->total_amount ?? 0), 2, '.', ''),
            ],
            'order_sales' => [
                'count' => (int) ($orderTotal->count ?? 0),
                'total_amount' => number_format((float) ($orderTotal->total_amount ?? 0), 2, '.', ''),
            ],
        ]);
    }

    public function totalCommission(Request $request, string $userId): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
        ]);

        $tenantId = $request->user()->tenant_id;

        $agent = \App\Models\User::where('tenant_id', $tenantId)->findOrFail($userId);
        $commissionRate = (float) ($agent->commission_rate ?? 0);

        $posTotal = DB::table('pos_transactions')
            ->where('tenant_id', $tenantId)
            ->where('created_by', $userId)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $data['date_from'])
            ->whereDate('created_at', '<=', $data['date_to'])
            ->sum('total');

        $commission = bcmul(
            (string) $posTotal,
            bcdiv((string) $commissionRate, '100', 8),
            8
        );

        return response()->json([
            'user_id' => $userId,
            'period' => ['from' => $data['date_from'], 'to' => $data['date_to']],
            'commission_rate' => $commissionRate,
            'total_sales' => number_format((float) $posTotal, 2, '.', ''),
            'commission_amount' => number_format((float) $commission, 2, '.', ''),
        ]);
    }
}
