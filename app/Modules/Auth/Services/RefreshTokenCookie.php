<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\ConfigurationException;
use Symfony\Component\HttpFoundation\Cookie;

class RefreshTokenCookie
{
    public function __construct(private readonly string $configurationKey) {}

    public function read(Request $request): ?string
    {
        $value = $request->cookie($this->name());

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function attach(
        JsonResponse $response,
        string $refreshToken,
        mixed $expiresAt,
    ): JsonResponse {
        $response->headers->setCookie(
            Cookie::create($this->name())
                ->withValue($refreshToken)
                ->withExpires($this->resolveExpiry($expiresAt))
                ->withPath($this->path())
                ->withDomain($this->domain())
                ->withSecure($this->secure())
                ->withHttpOnly(true)
                ->withRaw(false)
                ->withSameSite($this->sameSite()),
        );

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
        if (! is_array($payload)) {
            return null;
        }

        $tokens = is_array($payload['tokens'] ?? null) ? $payload['tokens'] : $payload;
        $value = $tokens['refresh_token'] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function extractExpiry(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return null;
        }

        $tokens = is_array($payload['tokens'] ?? null) ? $payload['tokens'] : $payload;

        return $tokens['refresh_token_expires_at'] ?? null;
    }

    private function name(): string
    {
        $name = trim((string) config($this->configurationKey.'.name', ''));

        return $name !== ''
            ? $name
            : throw new ConfigurationException('Refresh-token cookie name is not configured.');
    }

    private function path(): string
    {
        $path = trim((string) config($this->configurationKey.'.path', ''));

        return str_starts_with($path, '/')
            ? $path
            : throw new ConfigurationException('Refresh-token cookie path must be absolute.');
    }

    private function domain(): ?string
    {
        $domain = config($this->configurationKey.'.domain');

        return is_string($domain) && trim($domain) !== '' ? trim($domain) : null;
    }

    private function secure(): bool
    {
        return $this->sameSite() === 'none'
            || (bool) config($this->configurationKey.'.secure', app()->environment('production'));
    }

    private function sameSite(): string
    {
        $sameSite = strtolower((string) config($this->configurationKey.'.same_site', 'strict'));
        if (! in_array($sameSite, ['lax', 'strict', 'none'], true)) {
            throw new ConfigurationException('Refresh-token cookie SameSite configuration is invalid.');
        }

        return $sameSite;
    }

    private function resolveExpiry(mixed $expiresAt): DateTimeInterface
    {
        if ($expiresAt instanceof DateTimeInterface) {
            return $expiresAt;
        }

        if (is_string($expiresAt) && trim($expiresAt) !== '') {
            try {
                return new DateTimeImmutable($expiresAt);
            } catch (\Throwable $exception) {
                throw new ConfigurationException(
                    'The issued refresh token has an invalid expiry timestamp.',
                    previous: $exception,
                );
            }
        }

        throw new ConfigurationException('The issued refresh-token expiry is required for its cookie.');
    }
}
