<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Domain\Entities\AccessToken;

class AuthTokenResource extends JsonResource
{
    public function __construct(AccessToken $token)
    {
        // Pass the token as the underlying resource so JsonResource handles it correctly.
        parent::__construct($token);
    }

    public function toArray(Request $request): array
    {
        /** @var AccessToken $token */
        $token = $this->resource;

        return $token->toArray();
    }
}
