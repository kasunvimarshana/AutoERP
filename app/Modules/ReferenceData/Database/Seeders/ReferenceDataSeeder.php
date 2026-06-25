<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Database\Seeders;

use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ReferenceData\Constants\ReferenceDataPermission;
use Modules\ReferenceData\Models\CountryModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\ReferenceData\Models\LanguageModel;
use Modules\ReferenceData\Models\TimezoneModel;

final class ReferenceDataSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        $this->seedCatalogues();
        $this->seedPermissions();
    }

    private function seedCatalogues(): void
    {
        if (Schema::hasTable('countries')) {
            $country = CountryModel::query()->firstOrCreate(
                ['code' => 'LK'],
                ['name' => 'Sri Lanka', 'is_active' => true, 'row_version' => 1],
            );
            $this->ensureActive($country);
        }

        if (Schema::hasTable('currencies')) {
            $code = $this->defaultCurrencyCode();
            $currency = CurrencyModel::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $code === 'LKR' ? 'Sri Lankan Rupee' : $code,
                    'symbol' => $code === 'LKR' ? 'Rs' : $code,
                    'decimal_places' => 2,
                    'is_active' => true,
                    'row_version' => 1,
                ],
            );
            $this->ensureActive($currency);
        }

        if (Schema::hasTable('languages')) {
            $language = LanguageModel::query()->firstOrCreate(
                ['code' => 'en'],
                ['name' => 'English', 'native_name' => 'English', 'is_active' => true, 'row_version' => 1],
            );
            $this->ensureActive($language);
        }

        if (Schema::hasTable('timezones')) {
            foreach ([
                ['name' => 'UTC', 'display_name' => 'UTC'],
                ['name' => 'Asia/Colombo', 'display_name' => 'Sri Lanka Time'],
            ] as $timezone) {
                $model = TimezoneModel::query()->firstOrCreate(
                    ['name' => $timezone['name']],
                    ['display_name' => $timezone['display_name'], 'is_active' => true, 'row_version' => 1],
                );
                $this->ensureActive($model);
            }
        }
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('tenants')) {
            return;
        }

        $guard = (string) config('module-auth.protected_route_guard', 'auth-api');

        DB::transaction(function () use ($guard): void {
            foreach (DB::table('tenants')->where('status', '!=', 'archived')->pluck('id') as $tenantId) {
                foreach (ReferenceDataPermission::descriptions() as $name => $description) {
                    $identity = ['tenant_id' => (int) $tenantId, 'name' => $name, 'guard_name' => $guard];
                    $existing = DB::table('permissions')->where($identity)->first([
                        'id', 'organization_unit_id', 'module', 'description', 'row_version', 'deleted_at',
                    ]);
                    $values = [
                        'organization_unit_id' => null,
                        'module' => 'ReferenceData',
                        'description' => $description,
                        'deleted_at' => null,
                    ];

                    if ($existing === null) {
                        DB::table('permissions')->insert([
                            ...$identity,
                            ...$values,
                            'row_version' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } elseif (
                        $existing->organization_unit_id !== null
                        || $existing->module !== 'ReferenceData'
                        || $existing->description !== $description
                        || $existing->deleted_at !== null
                    ) {
                        DB::table('permissions')
                            ->where('tenant_id', (int) $tenantId)
                            ->where('id', $existing->id)
                            ->update([
                            ...$values,
                            'row_version' => max(1, (int) $existing->row_version) + 1,
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }, 3);
    }

    private function ensureActive(Model $model): void
    {
        if ((bool) $model->getAttribute('is_active')) {
            return;
        }

        $model->forceFill([
            'is_active' => true,
            'row_version' => max(1, (int) $model->getAttribute('row_version')) + 1,
        ])->save();
    }
}
