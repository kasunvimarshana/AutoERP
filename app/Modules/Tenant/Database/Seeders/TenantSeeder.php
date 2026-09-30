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
    private const UUID_NAME_PREFIX = 'autoerp.local/tenant/';

    private const INITIAL_STATUS_REASON = 'Awaiting platform onboarding.';

    public function run(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        DB::transaction(function (): void {
            $code = strtoupper(trim((string) config('tenant.seeding.tenant.code')));
            $name = trim((string) config('tenant.seeding.tenant.name'));

            TenantModel::query()->firstOrCreate(
                ['code' => $code],
                [
                    'uuid' => Uuid::uuid5(
                        Uuid::NAMESPACE_DNS,
                        self::UUID_NAME_PREFIX.$code,
                    )->toString(),
                    'name' => $name,
                    'slug' => Str::slug($code),
                    'status' => TenantStatus::DRAFT,
                    'status_changed_at' => now(),
                    'status_reason' => self::INITIAL_STATUS_REASON,
                    'activated_at' => null,
                    'row_version' => 1,
                ],
            );
        }, 3);
    }
}
