<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    /**
     * Wrap a callable in a try/catch, logging the error and re-throwing.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    protected function attempt(callable $callback, string $context = ''): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::error("BaseService error" . ($context ? " [{$context}]" : ''), [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Resolve the current tenant ID from the IoC container or fallback.
     */
    protected function currentTenantId(): string
    {
        if (app()->bound('current_tenant_id')) {
            return (string) app('current_tenant_id');
        }

        // Fallback: read from request attribute
        $request = app(\Illuminate\Http\Request::class);

        return (string) ($request->attributes->get('tenant_id') ?? $request->header('X-Tenant-ID', ''));
    }
}
