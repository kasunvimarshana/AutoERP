<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Services;

use InvalidArgumentException;
use Modules\ReferenceData\Contracts\ReferenceValueLookupInterface;
use Modules\ReferenceData\Models\CountryModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\ReferenceData\Models\LanguageModel;
use Modules\ReferenceData\Models\TimezoneModel;

final class ReferenceValueLookup implements ReferenceValueLookupInterface
{
    private const CATALOGS = [
        'countries' => [CountryModel::class, 'code'],
        'currencies' => [CurrencyModel::class, 'code'],
        'languages' => [LanguageModel::class, 'code'],
        'timezones' => [TimezoneModel::class, 'name'],
    ];

    public function supports(string $catalog): bool
    {
        return array_key_exists($catalog, self::CATALOGS);
    }

    public function activeValueExists(string $catalog, string|int $value): bool
    {
        [$model, $column] = self::CATALOGS[$catalog]
            ?? throw new InvalidArgumentException(
                "Unknown reference-data catalog [{$catalog}].",
            );

        return $model::query()
            ->where($column, $value)
            ->where('is_active', true)
            ->exists();
    }
}
