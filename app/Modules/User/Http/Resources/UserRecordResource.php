<?php

declare(strict_types=1);

namespace Modules\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class UserRecordResource extends JsonResource
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_hash',
        'remember_token',
        'token',
        'token_hash',
        'access_token',
        'refresh_token',
        'refresh_hash',
        'client_secret',
        'client_secret_hash',
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
            return $this->sanitize($this->resource);
        }

        return [];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function sanitize(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (in_array($key, self::SENSITIVE_KEYS, true)) {
                continue;
            }

            $sanitized[$key] = is_array($value) ? $this->sanitizeListOrMap($value) : $value;
        }

        return $sanitized;
    }

    private function sanitizeListOrMap(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(
                fn (mixed $entry): mixed => is_array($entry) ? $this->sanitizeListOrMap($entry) : $entry,
                $value,
            );
        }

        return $this->sanitize($value);
    }
}
