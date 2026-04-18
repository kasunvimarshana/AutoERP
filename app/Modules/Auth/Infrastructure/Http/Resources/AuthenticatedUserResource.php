<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Resources;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Auth-scoped representation of the current authenticated principal.
 */
class AuthenticatedUserResource extends JsonResource
{
    public function __construct(Authenticatable $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Authenticatable $user */
        $user = $this->resource;

        return [
            'id' => (int) $user->getAuthIdentifier(),
            'email' => $this->readScalar($user, 'email'),
            'first_name' => $this->readScalar($user, 'first_name'),
            'last_name' => $this->readScalar($user, 'last_name'),
            'status' => $this->readScalar($user, 'status'),
        ];
    }

    private function readScalar(Authenticatable $user, string $key): mixed
    {
        if (is_array($user) && array_key_exists($key, $user)) {
            return $user[$key];
        }

        if (isset($user->{$key}) || property_exists($user, $key)) {
            return $user->{$key};
        }

        return null;
    }
}
