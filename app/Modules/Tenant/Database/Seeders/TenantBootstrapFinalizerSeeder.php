<?php

declare(strict_types=1);

namespace Modules\Tenant\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Constants\TenantStatus;
use RuntimeException;

final class TenantBootstrapFinalizerSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasTable('tenant_domains')) {
            return;
        }

        $code = strtoupper(trim((string) env('AUTOERP_TENANT_CODE', 'AUTOERP')));

        DB::transaction(function () use ($code): void {
            $tenant = DB::table('tenants')
                ->where('code', $code)
                ->lockForUpdate()
                ->first();
            if ($tenant === null) {
                return;
            }

            $hasVerifiedPrimaryDomain = DB::table('tenant_domains')
                ->where('tenant_id', $tenant->id)
                ->where('is_primary', true)
                ->where('status', 'active')
                ->whereNotNull('verified_at')
                ->exists();

            if ($tenant->base_currency_id === null || ! $hasVerifiedPrimaryDomain) {
                if (app()->environment(['local', 'testing'])) {
                    throw new RuntimeException(
                        'The bootstrap tenant requires a base currency and verified primary domain.',
                    );
                }

                return;
            }

            DB::table('tenants')->where('id', $tenant->id)->update([
                'status' => TenantStatus::ACTIVE,
                'status_reason' => 'Bootstrap prerequisites verified.',
                'activated_at' => $tenant->activated_at ?? now(),
                'row_version' => max(1, (int) $tenant->row_version) + 1,
                'updated_at' => now(),
            ]);
        }, 3);
    }
}
