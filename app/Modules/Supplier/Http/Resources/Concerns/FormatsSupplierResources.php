<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Resources\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;

trait FormatsSupplierResources
{
    private function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function namedResource(?Model $model, bool $includeSymbol = false): ?array
    {
        if ($model === null) {
            return null;
        }

        $resource = [
            'id' => (int) $model->getKey(),
            'code' => $model->getAttribute('code'),
            'name' => $model->getAttribute('name'),
        ];
        if ($includeSymbol) {
            $resource['symbol'] = $model->getAttribute('symbol');
        }

        return $resource;
    }
}
