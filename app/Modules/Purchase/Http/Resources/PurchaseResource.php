<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Modules\Core\Services\DecimalMath;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class PurchaseResource extends JsonResource
{
    private ?DecimalMath $decimalMath = null;

    protected function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    protected function statusLabel(mixed $status): string
    {
        return str((string) $this->enumValue($status))
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>|null
     */
    protected function summary(mixed $model, array $fields): ?array
    {
        if ($model === null) {
            return null;
        }

        $data = ['id' => (int) $model->getKey()];
        foreach ($fields as $field) {
            if ($model->{$field} ?? null) {
                $data[$field] = $this->enumValue($model->{$field});
            }
        }

        if (! isset($data['name']) && isset($data['display_name'])) {
            $data['name'] = $data['display_name'];
        }

        return $data;
    }

    protected function compare(string $left, string $right): int
    {
        return $this->math()->compare($left, $right);
    }

    protected function add(string $left, string $right): string
    {
        return $this->math()->add($left, $right);
    }

    /**
     * @return array<string, mixed>
     */
    protected function arrayAttribute(string $key): array
    {
        $value = $this->resource->getAttribute($key);

        return is_array($value) ? $value : [];
    }

    private function math(): DecimalMath
    {
        return $this->decimalMath ??= new DecimalMath;
    }
}
