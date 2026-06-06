<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Exceptions\CurrentOrganizationUnitContextResolutionException;
use Symfony\Component\HttpFoundation\Response;

final class OrganizationUnitResolutionMiddleware
{
    public function __construct(
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organizationUnitId = $this->currentOrganizationUnit->currentOrganizationUnitId();

        if ($organizationUnitId === null || $organizationUnitId < 1) {
            if ($this->organizationUnitRequired()) {
                return $this->errorResponse(
                    'Organization unit context is required for the active request.',
                    Response::HTTP_BAD_REQUEST,
                );
            }

            return $next($request);
        }

        try {
            $this->guardOrganizationUnitSwitch($request, $organizationUnitId);
            $this->injectOrganizationUnitId($request, $organizationUnitId);
        } catch (CurrentOrganizationUnitContextResolutionException $exception) {
            Log::warning('organization_unit.access_violation', [
                'message' => $exception->getMessage(),
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            return $this->errorResponse($exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return $next($request);
    }

    private function guardOrganizationUnitSwitch(Request $request, int $resolvedOrganizationUnitId): void
    {
        $inputOrganizationUnitId = $this->toNullableInt($request->input('organization_unit_id'));
        if ($inputOrganizationUnitId !== null && $inputOrganizationUnitId !== $resolvedOrganizationUnitId) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Organization unit switching is not allowed within a request.',
            );
        }

        $routeOrganizationUnitId = $this->toNullableInt($request->route('organization_unit_id'));
        if ($routeOrganizationUnitId !== null && $routeOrganizationUnitId !== $resolvedOrganizationUnitId) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Organization unit switching is not allowed within a request.',
            );
        }

        $headerOrganizationUnitId = $this->toNullableInt($request->headers->get('X-Organization-Unit-Id'));
        if ($headerOrganizationUnitId !== null && $headerOrganizationUnitId !== $resolvedOrganizationUnitId) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Organization unit switching is not allowed within a request.',
            );
        }
    }

    private function injectOrganizationUnitId(Request $request, int $organizationUnitId): void
    {
        $request->attributes->set('organization_unit_id', $organizationUnitId);
        $request->merge(['organization_unit_id' => $organizationUnitId]);
    }

    private function organizationUnitRequired(): bool
    {
        return (bool) config('organization-unit.resolution.required', true);
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
