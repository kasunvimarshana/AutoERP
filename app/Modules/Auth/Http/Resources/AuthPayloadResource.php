<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class AuthPayloadResource extends JsonResource
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'token_hash',
        'refresh_hash',
        'challenge_hash',
        'code_hash',
        'client_secret_hash',
        'password',
        'password_hash',
        'challenge_secret',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DataRecord) {
            return $this->sanitize($this->resource->toArray());
        }

        if (is_array($this->resource)) {
            return $this->serializeAuthPayload($this->sanitize($this->resource));
        }

        if (is_bool($this->resource)) {
            return ['success' => $this->resource];
        }

        return [];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function serializeAuthPayload(array $payload): array
    {
        if (isset($payload['tokens']) && is_array($payload['tokens'])) {
            $userPayload = is_array($payload['user'] ?? null) ? $payload['user'] : [];

            return [
                'token' => $payload['tokens']['access_token'] ?? null,
                'refresh_token' => $payload['tokens']['refresh_token'] ?? null,
                'token_type' => $payload['tokens']['token_type'] ?? 'Bearer',
                'user' => $this->userSummary($userPayload),
                'tenant' => $this->tenantSummary($payload['tenant'] ?? null),
                'organization_unit' => $this->organizationUnitSummary($payload['organization_unit'] ?? null),
                'roles' => $this->stringList($payload['roles'] ?? $userPayload['roles'] ?? []),
                'permissions' => $this->stringList($payload['permissions'] ?? $userPayload['permissions'] ?? []),
                'session_id' => $payload['session']['id'] ?? null,
            ];
        }

        if (isset($payload['user']) && is_array($payload['user'])) {
            $userPayload = $payload['user'];

            return [
                'user' => $this->userSummary($userPayload),
                'tenant' => $this->tenantSummary($payload['tenant'] ?? null),
                'organization_unit' => $this->organizationUnitSummary($payload['organization_unit'] ?? null),
                'roles' => $this->stringList($payload['roles'] ?? $userPayload['roles'] ?? []),
                'permissions' => $this->stringList($payload['permissions'] ?? $userPayload['permissions'] ?? []),
            ];
        }

        return $payload;
    }

    /**
     * @param  array<string,mixed>  $user
     * @return array{id:int|string|null,name:string|null,username:string|null,email:string|null,roles:list<string>,permissions:list<string>}
     */
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
        ];
    }

    /**
     * @return array{id:int|string|null,code:string|null,name:string|null}|null
     */
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

    /**
     * @return array{id:int|string|null,code:string|null,name:string|null,features:array<string,mixed>|list<mixed>,enabled_modules:list<string>}|null
     */
    private function tenantSummary(mixed $relation): ?array
    {
        $summary = $this->relationSummary($relation);
        if ($summary === null || ! is_array($relation)) {
            return null;
        }

        return [
            ...$summary,
            'features' => is_array($relation['features'] ?? null) ? $relation['features'] : [],
            'enabled_modules' => $this->stringList($relation['enabled_modules'] ?? []),
        ];
    }

    /**
     * @return array{id:int|string|null,code:string|null,name:string|null,enabled_modules:list<string>}|null
     */
    private function organizationUnitSummary(mixed $relation): ?array
    {
        $summary = $this->relationSummary($relation);
        if ($summary === null || ! is_array($relation)) {
            return null;
        }

        return [
            ...$summary,
            'enabled_modules' => $this->stringList($relation['enabled_modules'] ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $normalized = trim((string) $item);
            if ($normalized !== '') {
                $items[] = $normalized;
            }
        }

        return array_values(array_unique($items));
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
