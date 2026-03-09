<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant from the request header and configures
 * per-tenant database connections and cache namespacing.
 */
class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $bypassPaths = config('tenant.bypass_paths', []);
        $normalizedPath = ltrim($request->path(), '/');

        foreach ($bypassPaths as $path) {
            if ($normalizedPath === $path || str_starts_with($normalizedPath, rtrim($path, '/').'/')) {
                return $next($request);
            }
        }

        $header = config('tenant.header', 'X-Tenant-ID');
        $tenantId = $request->header($header);

        if (empty($tenantId)) {
            return response()->json([
                'error'   => 'Missing tenant identifier',
                'message' => "The '{$header}' header is required.",
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match('/^[a-zA-Z0-9_\-]{1,64}$/', $tenantId)) {
            return response()->json([
                'error'   => 'Invalid tenant identifier',
                'message' => 'Tenant ID must be alphanumeric (1–64 characters).',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->configureTenantConnection($tenantId);

        app()->instance('current_tenant_id', $tenantId);
        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }

    private function configureTenantConnection(string $tenantId): void
    {
        $isolation = config('tenant.isolation', 'row');

        if ($isolation === 'database') {
            $template  = config('tenant.connection_template', 'tenant_template');
            $connName  = 'tenant_' . $tenantId;
            $prefix    = config('tenant.db_prefix', 'tenant_');
            $dbName    = $prefix . strtolower($tenantId);

            if (!Config::has("database.connections.{$connName}")) {
                $base = Config::get("database.connections.{$template}");
                $base['database'] = $dbName;
                Config::set("database.connections.{$connName}", $base);
            }

            DB::setDefaultConnection($connName);
        }
        // For 'row' and 'schema' isolation, tenant_id is passed via model scopes.
    }
}
