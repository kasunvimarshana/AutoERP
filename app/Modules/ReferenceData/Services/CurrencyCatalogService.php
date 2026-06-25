<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\ReferenceData\Models\CurrencyModel;

final class CurrencyCatalogService extends AbstractCatalogService
{
    protected function modelClass(): string
    {
        return CurrencyModel::class;
    }

    protected function resourceName(): string
    {
        return 'currency';
    }

    protected function normalizeCreate(array $data): array
    {
        return [
            'code' => strtoupper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'symbol' => $this->nullableString($data['symbol'] ?? null),
            'decimal_places' => (int) ($data['decimal_places'] ?? 2),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    protected function normalizeUpdate(array $data): array
    {
        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('symbol', $data)) {
            $attributes['symbol'] = $this->nullableString($data['symbol']);
        }

        return $attributes;
    }

    protected function assertStatusChangeAllowed(Model $model, bool $isActive): void
    {
        if ($isActive) {
            return;
        }

        $isTenantBaseCurrency = DB::table('tenants')
            ->where('base_currency_id', $model->getKey())
            ->where('status', '!=', 'archived')
            ->exists();

        if ($isTenantBaseCurrency) {
            throw ValidationException::withMessages([
                'is_active' => [
                    'This currency is the base currency of an active tenant and cannot be deactivated.',
                ],
            ]);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
