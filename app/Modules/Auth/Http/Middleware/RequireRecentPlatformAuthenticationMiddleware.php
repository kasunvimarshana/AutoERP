<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Symfony\Component\HttpFoundation\Response;

final class RequireRecentPlatformAuthenticationMiddleware
{
    public function __construct(private readonly CurrentUserContextAccessorInterface $currentUser) {}

    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->currentUser->current();
        $metadata = $context?->tokenPayload()['metadata'] ?? null;
        $metadata = is_array($metadata) ? $metadata : [];
        $authenticatedAt = $this->timestamp($metadata['authenticated_at'] ?? null);
        $mfaVerifiedAt = $this->timestamp($metadata['mfa_verified_at'] ?? null);
        $maximumAge = max(60, (int) config('module-auth.platform_mfa.step_up_ttl_seconds', 900));
        $threshold = now()->subSeconds($maximumAge)->getTimestamp();

        $requiresMfa = (bool) config('module-auth.platform_mfa.required', true);
        if ($authenticatedAt === null
            || $authenticatedAt < $threshold
            || ($requiresMfa && ($mfaVerifiedAt === null || $mfaVerifiedAt < $threshold))
        ) {
            return new JsonResponse([
                'message' => 'Recent platform authentication is required for this action.',
                'error' => [
                    'code' => 'PLATFORM_STEP_UP_REQUIRED',
                    'type' => 'authentication',
                    'details' => ['maximum_age_seconds' => $maximumAge],
                ],
            ], 401);
        }

        return $next($request);
    }

    private function timestamp(mixed $value): ?int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $timestamp = (int) $value;

            return $timestamp > 0 ? $timestamp : null;
        }

        return null;
    }
}
