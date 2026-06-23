<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\DTOs\DataRecord;
use Symfony\Component\HttpFoundation\Cookie;

final class RefreshTokenCookie
{
    public function read(Request $request): ?string
    {
        $value = $request->cookie($this->name());
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    public function attach(JsonResponse $response, string $refreshToken): JsonResponse
    {
        $response->headers->setCookie(Cookie::create(
            name: $this->name(),
            value: $refreshToken,
            expires: now()->addSeconds($this->ttlSeconds()),
            path: $this->path(),
            domain: $this->domain(),
            secure: $this->secure(),
            httpOnly: true,
            raw: false,
            sameSite: $this->sameSite(),
        ));

        return $response;
    }

    public function forget(JsonResponse $response): JsonResponse
    {
        $response->headers->clearCookie(
            $this->name(),
            $this->path(),
            $this->domain(),
            $this->secure(),
            true,
            $this->sameSite(),
        );

        return $response;
    }

    public function extract(mixed $payload): ?string
    {
        if ($payload instanceof DataRecord) {
            $payload = $payload->toArray();
        }
        if (! is_array($payload)) {
            return null;
        }

        $tokenPayload = isset($payload['tokens']) && is_array($payload['tokens'])
            ? $payload['tokens']
            : $payload;
        $refreshToken = $tokenPayload['refresh_token'] ?? null;
        if (! is_string($refreshToken)) {
            return null;
        }

        $refreshToken = trim($refreshToken);

        return $refreshToken !== '' ? $refreshToken : null;
    }

    private function name(): string
    {
        return (string) config('module-auth.web_refresh_cookie.name', 'autoerp_refresh_token');
    }

    private function path(): string
    {
        return (string) config('module-auth.web_refresh_cookie.path', '/api');
    }

    private function domain(): ?string
    {
        $domain = config('module-auth.web_refresh_cookie.domain');

        return is_string($domain) && trim($domain) !== '' ? trim($domain) : null;
    }

    private function secure(): bool
    {
        if ($this->sameSite() === 'none') {
            return true;
        }

        return (bool) config('module-auth.web_refresh_cookie.secure', app()->environment('production'));
    }

    private function sameSite(): string
    {
        $sameSite = strtolower((string) config('module-auth.web_refresh_cookie.same_site', 'strict'));

        return in_array($sameSite, ['lax', 'strict', 'none'], true) ? $sameSite : 'strict';
    }

    private function ttlSeconds(): int
    {
        return max(1, (int) config('module-auth.refresh_token_ttl_seconds', 2592000));
    }
}
