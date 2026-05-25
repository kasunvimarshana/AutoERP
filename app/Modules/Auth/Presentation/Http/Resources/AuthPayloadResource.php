<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\DTO\DataRecord;

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
            return $this->sanitize($this->resource);
        }

        return [];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
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
