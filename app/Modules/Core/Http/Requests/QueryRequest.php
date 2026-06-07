<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class QueryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->isMethod('GET')) {
            return;
        }

        $normalized = [];

        foreach ($this->rules() as $field => $rules) {
            if (! $this->hasBooleanRule($rules) || ! $this->query->has($field)) {
                continue;
            }

            $value = $this->normalizeBoolean($this->query->get($field));
            if ($value !== null) {
                $normalized[$field] = $value;
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    private function hasBooleanRule(mixed $rules): bool
    {
        foreach (is_array($rules) ? $rules : [$rules] as $rule) {
            if (is_string($rule) && in_array('boolean', explode('|', $rule), true)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1' || (is_string($value) && strtolower(trim($value)) === 'true')) {
            return true;
        }

        if ($value === 0 || $value === '0' || (is_string($value) && strtolower(trim($value)) === 'false')) {
            return false;
        }

        return null;
    }
}
