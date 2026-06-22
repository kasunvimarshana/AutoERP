<?php

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\AuthAccessTokenModel;
use Modules\Auth\Models\AuthAuthorizationCodeModel;
use Modules\Auth\Models\AuthRefreshTokenModel;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Events\TenantStatusChanged;

final class RevokeTenantAccessOnStatusChange
{
    public function handle(TenantStatusChanged $event): void
    {
        if ($event->newStatus === TenantStatus::ACTIVE) {
            return;
        }

        $now = now();
        DB::transaction(static function () use ($event, $now): void {
            AuthAuthorizationCodeModel::query()->where('tenant_id', $event->tenantId)->whereNotIn('status', ['consumed', 'revoked'])->update([
                'status' => 'revoked', 'revoked_at' => $now, 'row_version' => DB::raw('row_version + 1'), 'updated_at' => $now,
            ]);
            AuthRefreshTokenModel::query()->where('tenant_id', $event->tenantId)->where('status', 'active')->update([
                'status' => 'revoked', 'revoked_at' => $now, 'row_version' => DB::raw('row_version + 1'), 'updated_at' => $now,
            ]);
            AuthAccessTokenModel::query()->where('tenant_id', $event->tenantId)->where('status', 'active')->update([
                'status' => 'revoked', 'revoked_at' => $now, 'row_version' => DB::raw('row_version + 1'), 'updated_at' => $now,
            ]);
            AuthSessionModel::query()->where('tenant_id', $event->tenantId)->where('status', 'active')->update([
                'status' => 'revoked', 'revoked_at' => $now, 'row_version' => DB::raw('row_version + 1'), 'updated_at' => $now,
            ]);
        });
    }
}
