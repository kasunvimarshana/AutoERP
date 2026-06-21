<?php

declare(strict_types=1);

namespace Modules\Configuration\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Models\ConfigurationModel;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Configuration\Models\LanguageModel;
use Modules\Configuration\Models\TenantConfigurationModel;
use Modules\Configuration\Models\TimezoneModel;
use Database\Seeders\Concerns\ResolvesSeedContext;

final class ConfigurationSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedReferenceData();
            $this->seedConfigurationValues();
        }, 3);
    }

    private function seedReferenceData(): void
    {
        if (Schema::hasTable('languages')) {
            LanguageModel::query()->updateOrCreate(
                ['code' => 'en'],
                [
                    'name' => 'English',
                    'row_version' => 1,
                    'metadata' => json_encode(['seed_source' => 'configuration_module'], JSON_THROW_ON_ERROR),
                ],
            );
        }

        if (Schema::hasTable('timezones')) {
            TimezoneModel::query()->updateOrCreate(
                ['name' => 'UTC'],
                [
                    'offset' => '+00:00',
                    'row_version' => 1,
                    'metadata' => json_encode(['seed_source' => 'configuration_module'], JSON_THROW_ON_ERROR),
                ],
            );
        }

        if (Schema::hasTable('currencies')) {
            CurrencyModel::query()->updateOrCreate(
                ['code' => $this->defaultCurrencyCode()],
                [
                    'name' => $this->defaultCurrencyCode() === 'USD' ? 'US Dollar' : $this->defaultCurrencyCode(),
                    'symbol' => $this->defaultCurrencyCode() === 'USD' ? '$' : $this->defaultCurrencyCode(),
                    'decimal_places' => 2,
                    'is_active' => true,
                    'row_version' => 1,
                    'metadata' => json_encode(
                        ['seed_source' => 'configuration_module'],
                        JSON_THROW_ON_ERROR,
                    ),
                ],
            );
        }
    }

    private function seedConfigurationValues(): void
    {
        $values = [
            'app.locale' => 'en',
            'app.timezone' => 'UTC',
            'app.currency' => $this->defaultCurrencyCode(),
        ];

        if (Schema::hasTable('system_configurations')) {
            foreach ($values as $key => $value) {
                ConfigurationModel::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'value_type' => 'string',
                        'source' => 'database',
                        'description' => 'Default AutoERP configuration.',
                    ],
                );
            }
        }

        $tenant = $this->defaultTenant();
        if ($tenant === null || ! Schema::hasTable('tenant_configurations')) {
            return;
        }

        foreach ($values as $key => $value) {
            TenantConfigurationModel::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'key' => $key],
                [
                    'value' => $value,
                    'value_type' => 'string',
                    'source' => 'database',
                    'description' => 'Default AutoERP tenant configuration.',
                ],
            );
        }

        $currency = $this->defaultCurrency();
        if ($currency !== null && $tenant->currency_id !== $currency->getKey()) {
            $tenant->forceFill(['currency_id' => $currency->getKey()])->save();
        }
    }
}
