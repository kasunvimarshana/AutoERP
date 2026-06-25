<?php

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\AuthAccessTokenModel;
use Modules\Auth\Models\AuthAuthorizationCodeModel;
use Modules\Auth\Models\AuthRefreshTokenModel;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Core\Contracts\ClockInterface;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Events\TenantStatusChanged;

final class RevokeTenantAccessOnStatusChange
{
    private const CONSUMER_NAME = 'auth.revoke_tenant_access_on_status_change.v1';

    public function __construct(private readonly ClockInterface $clock) {}

    public function handle(TenantStatusChanged $event): void
    {
        if ($event->newStatus === TenantStatus::ACTIVE) {
            return;
        }

        DB::transaction(function () use ($event): void {
            $inserted = DB::table('auth_processed_integration_events')->insertOrIgnore([
                'event_uuid' => $event->eventId,
                'consumer_name' => self::CONSUMER_NAME,
                'tenant_id' => $event->tenantId,
                'processed_at' => $this->clock->now(),
            ]);
            if ($inserted === 0) {
                return;
            }

            $now = $this->clock->now();
            AuthAuthorizationCodeModel::query()
                ->where('tenant_id', $event->tenantId)
                ->whereNotIn('status', ['consumed', 'revoked'])
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => $now,
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_at' => $now,
                ]);
            AuthRefreshTokenModel::query()
                ->where('tenant_id', $event->tenantId)
                ->where('status', 'active')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => $now,
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_at' => $now,
                ]);
            AuthAccessTokenModel::query()
                ->where('tenant_id', $event->tenantId)
                ->where('status', 'active')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => $now,
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_at' => $now,
                ]);
            AuthSessionModel::query()
                ->where('tenant_id', $event->tenantId)
                ->where('status', 'active')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => $now,
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_at' => $now,
                ]);
        }, 3);
    }
}
