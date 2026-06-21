<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Services;

use DateTimeZone;
use Illuminate\Validation\ValidationException;
use Modules\ReferenceData\Models\TimezoneModel;

final class TimezoneCatalogService extends AbstractCatalogService
{
    protected function modelClass(): string
    {
        return TimezoneModel::class;
    }

    protected function resourceName(): string
    {
        return 'timezone';
    }

    protected function searchColumns(): array
    {
        return ['name', 'display_name'];
    }

    protected function orderColumn(): string
    {
        return 'display_name';
    }

    protected function normalizeCreate(array $data): array
    {
        $name = trim((string) $data['name']);

        if (! in_array($name, DateTimeZone::listIdentifiers(), true)) {
            throw ValidationException::withMessages([
                'name' => ['Enter a valid IANA timezone identifier.'],
            ]);
        }

        return [
            'name' => $name,
            'display_name' => trim((string) ($data['display_name'] ?? str_replace('_', ' ', $name))),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    protected function normalizeUpdate(array $data): array
    {
        if (! array_key_exists('display_name', $data)) {
            return [];
        }

        return ['display_name' => trim((string) $data['display_name'])];
    }
}
