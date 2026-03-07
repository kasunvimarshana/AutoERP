<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAbacPolicy
{
    /**
     * ABAC middleware: evaluates attribute-based policies.
     *
     * Usage: ->middleware('abac:products,update')
     *        First param = model/resource name, second = action
     */
    public function handle(Request $request, Closure $next, string $resource, string $action): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Attempt to resolve the policy via Laravel's Gate
        $gate   = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $result = false;

        try {
            // Try to find a route model binding or a request input for the resource
            $modelClass = $this->resolveModelClass($resource);
            $instance   = $this->resolveModelInstance($request, $resource, $modelClass);

            if ($instance) {
                $result = $gate->forUser($user)->check($action, $instance);
            } else {
                $result = $gate->forUser($user)->check($action, $modelClass);
            }
        } catch (\Exception) {
            // If no policy exists, fall back to permission check
            $result = $user->hasPermissionTo("{$resource}.{$action}");
        }

        // ABAC attribute check: validate tenant ownership
        if ($result) {
            $result = $this->checkTenantOwnership($request, $resource);
        }

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => "You are not authorized to {$action} this {$resource}.",
            ], 403);
        }

        return $next($request);
    }

    private function resolveModelClass(string $resource): string
    {
        $map = [
            'products'   => \App\Models\Product::class,
            'orders'     => \App\Models\Order::class,
            'users'      => \App\Models\User::class,
            'inventory'  => \App\Models\Inventory::class,
            'webhooks'   => \App\Models\Webhook::class,
        ];

        return $map[$resource] ?? 'App\\Models\\' . ucfirst($resource);
    }

    private function resolveModelInstance(Request $request, string $resource, string $modelClass): mixed
    {
        $id = $request->route($resource) ?? $request->route('id') ?? $request->input('id');

        if ($id && class_exists($modelClass)) {
            return $modelClass::find($id);
        }

        return null;
    }

    private function checkTenantOwnership(Request $request, string $resource): bool
    {
        if (!app()->bound('current_tenant')) {
            return true;
        }

        $tenantId = app('current_tenant')->id;
        $id       = $request->route($resource) ?? $request->route('id');

        if (!$id) {
            return true;
        }

        $modelClass = $this->resolveModelClass($resource);

        if (!class_exists($modelClass)) {
            return true;
        }

        return $modelClass::withoutGlobalScope('tenant')
                          ->where('id', $id)
                          ->where('tenant_id', $tenantId)
                          ->exists();
    }
}
