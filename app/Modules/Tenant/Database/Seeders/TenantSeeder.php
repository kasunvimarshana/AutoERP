<?php

declare(strict_types=1);

namespace Modules\Tenant\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Models\TenantModel;
use Ramsey\Uuid\Uuid;

final class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        DB::transaction(function (): void {
            $code = strtoupper(trim((string) env('AUTOERP_TENANT_CODE', 'AUTOERP')));

            TenantModel::query()->updateOrCreate(
                ['code' => $code],
                [
                    'uuid' => Uuid::uuid5(
                        Uuid::NAMESPACE_DNS,
                        'autoerp.local/tenant/'.$code,
                    )->toString(),
                    'name' => trim((string) env('AUTOERP_TENANT_NAME', 'AutoERP')),
                    'slug' => Str::slug($code),
                    'status' => TenantStatus::DRAFT,
                    'status_changed_at' => now(),
                    'status_reason' => 'Awaiting platform onboarding.',
                    'activated_at' => null,
                    'row_version' => 1,
                ],
            );
        }, 3);
    }
}
