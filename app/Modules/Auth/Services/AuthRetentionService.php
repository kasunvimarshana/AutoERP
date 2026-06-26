<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use DateTimeInterface;
use Illuminate\Database\DatabaseManager;
use Modules\Auth\Enums\AuthorizationCodeStatus;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Enums\TokenStatus;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final readonly class AuthRetentionService
{
    private const REFRESH_PURGE_BATCH_SIZE = 500;

    public function __construct(
        private DatabaseManager $database,
        private ClockInterface $clock,
        private TenantExecutionContextInterface $executionContext,
    ) {}

    /** @return array<string,int> */
    public function purge(): array
    {
        return $this->executionContext->runAsControlPlane(function (): array {
            $now = $this->clock->now();
            $codeCutoff = $now->modify('-'.max(1, (int) config('module-auth.retention.authorization_codes_days', 7)).' days');
            $attemptCutoff = $now->modify('-'.max(1, (int) config('module-auth.retention.login_attempts_days', 90)).' days');
            $tokenCutoff = $now->modify('-'.max(1, (int) config('module-auth.retention.terminal_tokens_days', 30)).' days');
            $sessionCutoff = $now->modify('-'.max(1, (int) config('module-auth.retention.terminal_sessions_days', 90)).' days');
            $eventCutoff = $now->modify('-'.max(1, (int) config('module-auth.retention.processed_events_days', 30)).' days');
            $deliveryCutoff = $now->modify('-'.max(1, (int) config('module-auth.retention.invitation_deliveries_days', 90)).' days');

            return $this->database->transaction(function () use (
                $now,
                $codeCutoff,
                $attemptCutoff,
                $tokenCutoff,
                $sessionCutoff,
                $eventCutoff,
                $deliveryCutoff,
            ): array {
                $counts = [];

                $counts['authorization_codes'] = $this->database->table('auth_authorization_codes')
                    ->where('expires_at', '<', $codeCutoff)
                    ->whereIn('status', [
                        AuthorizationCodeStatus::CONSUMED->value,
                        AuthorizationCodeStatus::REVOKED->value,
                        AuthorizationCodeStatus::EXPIRED->value,
                        AuthorizationCodeStatus::ACTIVE->value,
                    ])->delete();

                $counts['tenant_login_attempts'] = $this->database->table('auth_login_attempts')
                    ->where('attempted_at', '<', $attemptCutoff)->delete();
                $counts['platform_login_attempts'] = $this->database->table('auth_platform_login_attempts')
                    ->where('attempted_at', '<', $attemptCutoff)->delete();

                $terminalTokens = [
                    TokenStatus::ROTATED->value,
                    TokenStatus::REVOKED->value,
                    TokenStatus::EXPIRED->value,
                ];
                $counts['tenant_refresh_tokens'] = $this->purgeTenantRefreshTokens(
                    $tokenCutoff,
                    $terminalTokens,
                );
                $counts['platform_refresh_tokens'] = $this->database->table('auth_platform_refresh_tokens')
                    ->where(function ($query) use ($terminalTokens, $tokenCutoff): void {
                        $query->whereIn('status', $terminalTokens)
                            ->where('updated_at', '<', $tokenCutoff);
                    })->orWhere('expires_at', '<', $tokenCutoff)->delete();

                $terminalAccess = [TokenStatus::REVOKED->value, TokenStatus::EXPIRED->value];
                $counts['tenant_access_tokens'] = $this->database->table('auth_access_tokens')
                    ->where(function ($query) use ($terminalAccess, $tokenCutoff): void {
                        $query->whereIn('status', $terminalAccess)
                            ->where('updated_at', '<', $tokenCutoff);
                    })->orWhere('expires_at', '<', $tokenCutoff)->delete();
                $counts['platform_access_tokens'] = $this->database->table('auth_platform_access_tokens')
                    ->where(function ($query) use ($terminalAccess, $tokenCutoff): void {
                        $query->whereIn('status', $terminalAccess)
                            ->where('updated_at', '<', $tokenCutoff);
                    })->orWhere('expires_at', '<', $tokenCutoff)->delete();

                $terminalSessions = [SessionStatus::REVOKED->value, SessionStatus::EXPIRED->value];
                $counts['tenant_sessions'] = $this->database->table('auth_sessions')
                    ->where(function ($query) use ($terminalSessions, $sessionCutoff): void {
                        $query->whereIn('status', $terminalSessions)
                            ->where('updated_at', '<', $sessionCutoff);
                    })->orWhere('expires_at', '<', $sessionCutoff)->delete();
                $counts['platform_sessions'] = $this->database->table('auth_platform_sessions')
                    ->where(function ($query) use ($terminalSessions, $sessionCutoff): void {
                        $query->whereIn('status', $terminalSessions)
                            ->where('updated_at', '<', $sessionCutoff);
                    })->orWhere('expires_at', '<', $sessionCutoff)->delete();

                $counts['processed_events'] = $this->database->table('auth_processed_integration_events')
                    ->where('processed_at', '<', $eventCutoff)->delete();
                $counts['invitation_deliveries'] = $this->database->table('auth_registration_invitation_deliveries')
                    ->where('updated_at', '<', $deliveryCutoff)
                    ->whereNotIn('status', ['queued', 'sending'])
                    ->delete();

                return $counts;
            }, 3);
        });
    }
    /** @param list<string> $terminalStatuses */
    private function purgeTenantRefreshTokens(
        DateTimeInterface $cutoff,
        array $terminalStatuses,
    ): int {
        $deletedTotal = 0;

        do {
            $candidateIds = $this->database->table('auth_refresh_tokens as candidate')
                ->where(function ($query) use ($terminalStatuses, $cutoff): void {
                    $query->where(function ($terminal) use ($terminalStatuses, $cutoff): void {
                        $terminal->whereIn('candidate.status', $terminalStatuses)
                            ->where('candidate.updated_at', '<', $cutoff);
                    })->orWhere('candidate.expires_at', '<', $cutoff);
                })
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('auth_refresh_tokens as child')
                        ->whereColumn('child.parent_refresh_token_id', 'candidate.id');
                })
                ->orderByDesc('candidate.id')
                ->limit(self::REFRESH_PURGE_BATCH_SIZE)
                ->pluck('candidate.id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            if ($candidateIds === []) {
                break;
            }

            $deleted = $this->database->table('auth_refresh_tokens')
                ->whereIn('id', $candidateIds)
                ->delete();

            $deletedTotal += $deleted;
        } while ($deleted > 0);

        return $deletedTotal;
    }

}
