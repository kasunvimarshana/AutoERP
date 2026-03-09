<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{
    private string $accessToken;
    private ?string $refreshToken;
    private int $expiresIn;

    public function __construct(
        $resource,
        string $accessToken,
        ?string $refreshToken = null,
        int $expiresIn = 0
    ) {
        parent::__construct($resource);
        $this->accessToken  = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresIn    = $expiresIn;
    }

    public function toArray(Request $request): array
    {
        return [
            'access_token'  => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => $this->expiresIn,
        ];
    }
}
