<?php
namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
class HealthController extends Controller {
    public function check(): JsonResponse {
        $status = 'healthy'; $checks = [];
        try { DB::connection()->getPdo(); $checks['database'] = 'healthy'; }
        catch (\Exception $e) { $checks['database'] = 'unhealthy'; $status = 'unhealthy'; }
        return response()->json(['service' => config('app.name'), 'status' => $status, 'timestamp' => now()->toISOString(), 'checks' => $checks, 'version' => '1.0.0'], $status === 'healthy' ? 200 : 503);
    }
}
