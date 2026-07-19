<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use BackedEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;

abstract class RentalResource extends JsonResource
{
    protected function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    protected function summary(mixed $model, array $fields): ?array
    {
        if ($model === null) {
            return null;
        }
        $data = ['id' => (int) $model->getKey()];
        foreach ($fields as $field) {
            $value = $model->{$field} ?? null;
            $data[$field] = $value instanceof BackedEnum ? $value->value : $value;
        }
        return $data;
    }

    protected function decimal(mixed $value): string
    {
        return app(DecimalMath::class)->normalize((string) ($value ?? '0'));
    }

    protected function loadedCollection(string $relation, callable $mapper): array
    {
        if (! $this->resource->relationLoaded($relation)) {
            return [];
        }
        $value = $this->resource->getRelation($relation);
        return $value instanceof Collection ? $value->map($mapper)->values()->all() : [];
    }
}
