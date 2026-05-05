<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Disable automatic resource class inference to prevent mapInto from passing
     * collection keys as the second constructor argument of UserResource.
     */
    protected function collects(): ?string
    {
        return null;
    }

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $user) use ($request): array {
                if ($user instanceof UserResource) {
                    return $user->toArray($request);
                }

                return (new UserResource($user))->toArray($request);
            })
            ->all();
    }
}
