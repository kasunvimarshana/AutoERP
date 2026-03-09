<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Token API Resource
 *
 * Transforms token data for API responses.
 */
class TokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource['access_token'],
            'token_type' => $this->resource['token_type'] ?? 'Bearer',
        ];
    }
}
