<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;

final class TenantPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $provisioner = app(TenantAccessProvisionerInterface::class);

        DB::table('tenants')
            ->where('status', '!=', 'archived')
            ->orderBy('id')
            ->pluck('id')
            ->each(static function ($tenantId) use ($provisioner): void {
                $provisioner->provision((int) $tenantId);
            });
    }
}
