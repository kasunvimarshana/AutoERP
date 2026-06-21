<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Services;

use Modules\ReferenceData\Models\LanguageModel;

final class LanguageCatalogService extends AbstractCatalogService
{
    protected function modelClass(): string
    {
        return LanguageModel::class;
    }

    protected function resourceName(): string
    {
        return 'language';
    }

    protected function normalizeCreate(array $data): array
    {
        return [
            'code' => strtolower(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'native_name' => $this->nullableString($data['native_name'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    protected function normalizeUpdate(array $data): array
    {
        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('native_name', $data)) {
            $attributes['native_name'] = $this->nullableString($data['native_name']);
        }

        return $attributes;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
