<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Exceptions\CurrentTenantContextResolutionException;
use Symfony\Component\HttpFoundation\Response;

final class TenantResolutionMiddleware
{
    public function __construct(private readonly CurrentTenantContextAccessorInterface $currentTenant)
    {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null || $tenantId < 1) {
            if ($this->tenantRequired()) {
                return $this->errorResponse(
                    'Tenant context is required for the active request.',
                    Response::HTTP_BAD_REQUEST,
                );
            }

            return $next($request);
        }

        try {
            $this->guardTenantSwitch($request, $tenantId);
            $this->injectTenantId($request, $tenantId);
        } catch (CurrentTenantContextResolutionException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return $next($request);
    }

    private function guardTenantSwitch(Request $request, int $resolvedTenantId): void
    {
        $inputTenantId = $this->toNullableInt($request->input('tenant_id'));
        if ($inputTenantId !== null && $inputTenantId !== $resolvedTenantId) {
            throw new CurrentTenantContextResolutionException('Tenant switching is not allowed within a request.');
        }

        $routeTenantId = $this->toNullableInt($request->route('tenant_id'));
        if ($routeTenantId !== null && $routeTenantId !== $resolvedTenantId) {
            throw new CurrentTenantContextResolutionException('Tenant switching is not allowed within a request.');
        }

        $headerTenantId = $this->toNullableInt($request->headers->get('X-Tenant-Id'));
        if ($headerTenantId !== null && $headerTenantId !== $resolvedTenantId) {
            throw new CurrentTenantContextResolutionException('Tenant switching is not allowed within a request.');
        }
    }

    private function injectTenantId(Request $request, int $tenantId): void
    {
        $request->attributes->set('tenant_id', $tenantId);
        $request->merge(['tenant_id' => $tenantId]);
    }

    private function tenantRequired(): bool
    {
        return (bool) config('tenant.resolution.required', true);
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
