<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Services;

use Modules\ReferenceData\Models\CountryModel;

final class CountryCatalogService extends AbstractCatalogService
{
    protected function modelClass(): string
    {
        return CountryModel::class;
    }

    protected function resourceName(): string
    {
        return 'country';
    }

    protected function normalizeCreate(array $data): array
    {
        return [
            'code' => strtoupper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'phone_code' => $this->nullableString($data['phone_code'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    protected function normalizeUpdate(array $data): array
    {
        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('phone_code', $data)) {
            $attributes['phone_code'] = $this->nullableString($data['phone_code']);
        }

        return $attributes;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
