<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\DTOs\DataRecord;
use Symfony\Component\HttpFoundation\Cookie;

class RefreshTokenCookie
{
    public function __construct(private readonly string $configurationKey) {}
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
        $cookie = Cookie::create($this->name())
            ->withValue($refreshToken)
            ->withExpires(now()->addSeconds($this->ttlSeconds()))
            ->withPath($this->path())
            ->withDomain($this->domain())
            ->withSecure($this->secure())
            ->withHttpOnly(true)
            ->withRaw(false)
            ->withSameSite($this->sameSite());

        $response->headers->setCookie($cookie);

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
        $name = trim((string) config($this->configurationKey.'.name', ''));

        return $name !== '' ? $name : throw new \LogicException('Refresh-token cookie name is not configured.');
    }

    private function path(): string
    {
        $path = trim((string) config($this->configurationKey.'.path', ''));

        return str_starts_with($path, '/') ? $path : throw new \LogicException('Refresh-token cookie path must be absolute.');
    }

    private function domain(): ?string
    {
        $domain = config($this->configurationKey.'.domain');

        return is_string($domain) && trim($domain) !== '' ? trim($domain) : null;
    }

    private function secure(): bool
    {
        if ($this->sameSite() === 'none') {
            return true;
        }

        return (bool) config($this->configurationKey.'.secure', app()->environment('production'));
    }

    private function sameSite(): string
    {
        $sameSite = strtolower((string) config($this->configurationKey.'.same_site', 'strict'));

        return in_array($sameSite, ['lax', 'strict', 'none'], true) ? $sameSite : 'strict';
    }

    private function ttlSeconds(): int
    {
        return max(1, (int) config('module-auth.refresh_token_ttl_seconds', 2592000));
    }
}
