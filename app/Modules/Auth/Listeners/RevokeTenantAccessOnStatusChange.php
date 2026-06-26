<?php

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\AuthorizationCodeStatus;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Enums\TokenStatus;
use Modules\Auth\Models\AuthAccessTokenModel;
use Modules\Auth\Models\AuthAuthorizationCodeModel;
use Modules\Auth\Models\AuthRefreshTokenModel;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Core\Contracts\ClockInterface;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Events\TenantStatusChanged;

final readonly class RevokeTenantAccessOnStatusChange
{
    private const SOURCE_SYSTEM = 'tenant';
    private const EVENT_TYPE = 'tenant.status_changed';
    private const REVOCATION_REASON = 'Tenant is not active.';

    public function __construct(private ClockInterface $clock) {}

    public function handle(TenantStatusChanged $event): void
    {
        if ($event->newStatus === TenantStatus::ACTIVE) {
            return;
        }

        DB::transaction(function () use ($event): void {
            $inserted = DB::table('auth_processed_integration_events')->insertOrIgnore([
                'tenant_id' => $event->tenantId,
                'source_system' => self::SOURCE_SYSTEM,
                'event_id' => $event->eventId,
                'event_type' => self::EVENT_TYPE,
                'processed_at' => $this->clock->now(),
                'created_at' => $this->clock->now(),
                'updated_at' => $this->clock->now(),
            ]);
            if ($inserted === 0) {
                return;
            }

            $now = $this->clock->now();
            AuthAuthorizationCodeModel::query()
                ->where('tenant_id', $event->tenantId)
                ->where('status', AuthorizationCodeStatus::ACTIVE->value)
                ->increment('row_version', 1, [
                    'status' => AuthorizationCodeStatus::REVOKED->value,
                    'revoked_at' => $now,
                    'updated_at' => $now,
                ]);
            AuthRefreshTokenModel::query()
                ->where('tenant_id', $event->tenantId)
                ->where('status', TokenStatus::ACTIVE->value)
                ->increment('row_version', 1, [
                    'status' => TokenStatus::REVOKED->value,
                    'revoked_at' => $now,
                    'revocation_reason' => self::REVOCATION_REASON,
                    'updated_at' => $now,
                ]);
            AuthAccessTokenModel::query()
                ->where('tenant_id', $event->tenantId)
                ->where('status', TokenStatus::ACTIVE->value)
                ->increment('row_version', 1, [
                    'status' => TokenStatus::REVOKED->value,
                    'revoked_at' => $now,
                    'revocation_reason' => self::REVOCATION_REASON,
                    'updated_at' => $now,
                ]);
            AuthSessionModel::query()
                ->where('tenant_id', $event->tenantId)
                ->where('status', SessionStatus::ACTIVE->value)
                ->increment('row_version', 1, [
                    'status' => SessionStatus::REVOKED->value,
                    'revoked_at' => $now,
                    'revocation_reason' => self::REVOCATION_REASON,
                    'updated_at' => $now,
                ]);
        }, 3);
    }
}
