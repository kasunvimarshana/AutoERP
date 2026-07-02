<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthPayloadResource extends JsonResource
{
    private const SENSITIVE_KEYS = [
        'refresh_token',
        'token_digest',
        'refresh_digest',
        'code_digest',
        'client_secret_hash',
        'password',
        'password_hash',
        'secret',
    ];

    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        if (! is_array($this->resource)) {
            return [];
        }

        $payload = $this->sanitize($this->resource);
        if (! isset($payload['tokens']) || ! is_array($payload['tokens'])) {
            return $this->profile($payload);
        }

        return array_merge($this->profile($payload), [
            'token' => $payload['tokens']['access_token'] ?? null,
            'token_type' => $payload['tokens']['token_type'] ?? 'Bearer',
            'token_expires_at' => $payload['tokens']['access_token_expires_at'] ?? null,
            'session' => is_array($payload['session'] ?? null) ? $payload['session'] : null,
        ]);
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function profile(array $payload): array
    {
        $user = is_array($payload['user'] ?? null) ? $payload['user'] : [];

        return [
            'user' => $this->userSummary($user),
            'tenant' => $this->relationSummary($payload['tenant'] ?? null),
            'organization_unit' => $this->relationSummary($payload['organization_unit'] ?? null),
            'roles' => $this->stringList($payload['roles'] ?? $user['roles'] ?? []),
            'permissions' => $this->stringList($payload['permissions'] ?? $user['permissions'] ?? []),
            'enabled_modules' => is_array($payload['enabled_modules'] ?? null)
                ? $this->stringList($payload['enabled_modules'])
                : null,
            'is_platform_operator' => (bool) ($payload['is_platform_operator'] ?? $user['is_platform_operator'] ?? false),
        ];
    }

    /** @param array<string,mixed> $user @return array<string,mixed> */
    private function userSummary(array $user): array
    {
        $name = trim(implode(' ', array_filter([
            isset($user['first_name']) ? (string) $user['first_name'] : null,
            isset($user['last_name']) ? (string) $user['last_name'] : null,
        ])));

        return [
            'id' => $user['id'] ?? null,
            'name' => $name !== '' ? $name : ($user['name'] ?? $user['email'] ?? null),
            'username' => isset($user['username']) ? (string) $user['username'] : null,
            'email' => isset($user['email']) ? (string) $user['email'] : null,
            'roles' => $this->stringList($user['roles'] ?? []),
            'permissions' => $this->stringList($user['permissions'] ?? []),
            'is_platform_operator' => (bool) ($user['is_platform_operator'] ?? false),
        ];
    }

    /** @return array{id:int|string|null,code:string|null,name:string|null}|null */
    private function relationSummary(mixed $relation): ?array
    {
        if (! is_array($relation)) {
            return null;
        }

        return [
            'id' => $relation['id'] ?? null,
            'code' => isset($relation['code']) ? (string) $relation['code'] : null,
            'name' => isset($relation['name']) ? (string) $relation['name'] : null,
        ];
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }
        $result = [];
        foreach ($value as $entry) {
            if (is_scalar($entry) && trim((string) $entry) !== '') {
                $result[] = trim((string) $entry);
            }
        }
        return array_values(array_unique($result));
    }

    private function sanitize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $sanitized = [];
        foreach ($value as $key => $entry) {
            if (is_string($key) && in_array($key, self::SENSITIVE_KEYS, true)) {
                continue;
            }
            $sanitized[$key] = $this->sanitize($entry);
        }
        return $sanitized;
    }
}
