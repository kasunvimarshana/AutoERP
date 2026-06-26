<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Services\Mfa\PlatformMfaPolicy;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequireRecentPlatformAuthenticationMiddleware
{
    public function __construct(
        private CurrentUserContextAccessorInterface $currentUser,
        private PlatformMfaPolicy $mfaPolicy,
        private ClockInterface $clock,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $payload = $this->currentUser->current()?->tokenPayload() ?? [];
        $maximumAge = max(60, (int) config('module-auth.platform_mfa.step_up_ttl_seconds', 900));
        $threshold = $this->clock->now()->getTimestamp() - $maximumAge;

        $authenticatedAt = $this->timestamp($payload['authenticated_at'] ?? null);
        $mfaVerifiedAt = $this->timestamp($payload['mfa_verified_at'] ?? null);
        $passwordRequired = $authenticatedAt === null || $authenticatedAt < $threshold;
        $mfaRequired = $this->mfaPolicy->isMfaStepUpRequired()
            && ($mfaVerifiedAt === null || $mfaVerifiedAt < $threshold);

        if (! $passwordRequired && ! $mfaRequired) {
            return $next($request);
        }

        $requiredFactor = $passwordRequired ? 'password' : 'mfa';
        $response = new JsonResponse([
            'message' => $requiredFactor === 'mfa'
                ? 'Recent multi-factor verification is required for this action.'
                : 'Recent platform authentication is required for this action.',
            'code' => 'PLATFORM_STEP_UP_REQUIRED',
            'details' => [
                'maximum_age_seconds' => $maximumAge,
                'required_factor' => $requiredFactor,
            ],
        ], 401);
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }

    private function timestamp(mixed $value): ?int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $timestamp = (int) $value;
            return $timestamp > 0 ? $timestamp : null;
        }
        if (is_string($value) && trim($value) !== '') {
            try {
                return (new DateTimeImmutable($value))->getTimestamp();
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }
}
