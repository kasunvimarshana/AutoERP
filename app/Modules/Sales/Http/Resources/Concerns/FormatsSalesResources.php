<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources\Concerns;

trait FormatsSalesResources
{
    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    private function statusLabel(mixed $status): string
    {
        return str((string) $this->enumValue($status))
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function summary(mixed $model, array $fields): ?array
    {
        if ($model === null) {
            return null;
        }
        $data = ['id' => (int) $model->getKey()];
        foreach ($fields as $field) {
            $value = $model->{$field} ?? null;
            if ($value !== null && $value !== '') {
                $data[$field] = $this->enumValue($value);
            }
        }
        if (! isset($data['name']) && isset($data['display_name'])) {
            $data['name'] = $data['display_name'];
        }

        return $data;
    }
}
