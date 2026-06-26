<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use Modules\Auth\Constants\InvitationDeliveryStatus;
use Modules\Auth\Models\AuthRegistrationInvitationDeliveryModel;
use Modules\Core\Contracts\AuthInvitationDeliveryHealthReaderInterface;
use Modules\Core\Contracts\ClockInterface;

final readonly class InvitationDeliveryHealthReader implements AuthInvitationDeliveryHealthReaderInterface
{
    public function __construct(
        private AuthRegistrationInvitationDeliveryModel $deliveries,
        private ClockInterface $clock,
    ) {}

    public function health(?int $tenantId = null): array
    {
        $staleBefore = $this->clock->now()->modify(sprintf(
            '-%d seconds',
            max(60, (int) config('module-auth.registration.delivery_stale_after_seconds', 900)),
        ));

        $baseQuery = $this->deliveries->newQuery();
        if ($tenantId !== null) {
            $baseQuery->where('tenant_id', $tenantId);
        }

        $rawCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();
        $counts = [];
        foreach (InvitationDeliveryStatus::values() as $status) {
            $counts[$status] = (int) ($rawCounts[$status] ?? 0);
        }

        $stale = (clone $baseQuery)
            ->where(function ($query) use ($staleBefore): void {
                $query->where(function ($queued) use ($staleBefore): void {
                    $queued->where('status', InvitationDeliveryStatus::QUEUED)
                        ->where('requested_at', '<=', $staleBefore);
                })->orWhere(function ($sending): void {
                    $sending->where('status', InvitationDeliveryStatus::SENDING)
                        ->where('lease_expires_at', '<=', $this->clock->now());
                });
            })
            ->count();

        return [
            'counts' => $counts,
            'failed' => $counts[InvitationDeliveryStatus::FAILED] ?? 0,
            'stale' => $stale,
        ];
    }

    public function failed(int $limit = 20): array
    {
        return $this->deliveries->newQuery()
            ->join('auth_registration_invitations', function ($join): void {
                $join->on(
                    'auth_registration_invitations.id',
                    '=',
                    'auth_registration_invitation_deliveries.invitation_id',
                )->on(
                    'auth_registration_invitations.tenant_id',
                    '=',
                    'auth_registration_invitation_deliveries.tenant_id',
                );
            })
            ->where('auth_registration_invitation_deliveries.status', InvitationDeliveryStatus::FAILED)
            ->orderByDesc('auth_registration_invitation_deliveries.failed_at')
            ->limit(max(1, min($limit, 100)))
            ->get([
                'auth_registration_invitation_deliveries.public_id',
                'auth_registration_invitation_deliveries.tenant_id',
                'auth_registration_invitation_deliveries.attempt_number',
                'auth_registration_invitation_deliveries.processing_attempt_count',
                'auth_registration_invitation_deliveries.error_code',
                'auth_registration_invitation_deliveries.error_message',
                'auth_registration_invitation_deliveries.failed_at',
                'auth_registration_invitations.email',
            ])
            ->map(static fn ($row): array => [
                'public_id' => (string) $row->public_id,
                'tenant_id' => (int) $row->tenant_id,
                'email' => (string) $row->email,
                'attempt_number' => (int) $row->attempt_number,
                'processing_attempt_count' => (int) $row->processing_attempt_count,
                'error_code' => $row->error_code === null ? null : (string) $row->error_code,
                'error_message' => $row->error_message === null ? null : (string) $row->error_message,
                'failed_at' => $row->failed_at?->toAtomString(),
            ])
            ->all();
    }
}
