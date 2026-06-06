<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;

trait FormatsVehicleResources
{
    private function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function namedResource(?Model $model, ?string $numberKey = null): ?array
    {
        if ($model === null) {
            return null;
        }

        $resource = [
            'id' => (int) $model->getKey(),
            'code' => $model->getAttribute('code'),
            'name' => $model->getAttribute('name'),
        ];

        if ($numberKey !== null) {
            $resource[$numberKey] = $model->getAttribute($numberKey);
        }

        return $resource;
    }
}
