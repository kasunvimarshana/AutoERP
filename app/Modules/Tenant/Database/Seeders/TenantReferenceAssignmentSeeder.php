<?php

declare(strict_types=1);

namespace Modules\Tenant\Database\Seeders;

use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Models\TenantModel;

final class TenantReferenceAssignmentSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasColumn('tenants', 'base_currency_id')) {
            return;
        }

        $currency = $this->defaultCurrency();
        if ($currency === null) {
            return;
        }

        TenantModel::query()
            ->whereNull('base_currency_id')
            ->update([
                'base_currency_id' => $currency->getKey(),
                'row_version' => DB::raw('row_version + 1'),
                'updated_at' => now(),
            ]);
    }
}
