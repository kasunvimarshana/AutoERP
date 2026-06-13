<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources\Concerns;

use BackedEnum;

trait FormatsInvoiceResources
{
    protected function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
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

        $summary = ['id' => (int) $model->getKey()];
        foreach ($fields as $field) {
            $summary[$field] = $this->enumValue($model->{$field} ?? null);
        }

        return $summary;
    }
}
