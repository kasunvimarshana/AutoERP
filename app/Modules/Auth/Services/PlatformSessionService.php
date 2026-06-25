<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Auth\Models\AuthPlatformAccessTokenModel;
use Modules\Auth\Models\AuthPlatformMfaMethodModel;
use Modules\Auth\Models\AuthPlatformSessionModel;
use Modules\Auth\Models\AuthPlatformRefreshTokenModel;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\PlatformOperatorSessionRevokerInterface;
use Modules\User\Models\UserModel;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PlatformSessionService implements PlatformOperatorSessionRevokerInterface
{
    public function __construct(
        private readonly AuthPlatformSessionModel $sessions,
        private readonly AuthPlatformAccessTokenModel $accessTokens,
        private readonly AuthPlatformRefreshTokenModel $refreshTokens,
        private readonly AuthPlatformMfaMethodModel $mfaMethods,
        private readonly UserModel $users,
        private readonly ClockInterface $clock,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly DatabaseManager $database,
        private readonly AuditRecorderInterface $audit,
    ) {}

    public function create(
        int $userId,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $deviceName,
        int $lifetimeSeconds,
    ): AuthPlatformSessionModel {
        return $this->executionContext->runAsControlPlane(function () use (
            $userId,
            $ipAddress,
            $userAgent,
            $deviceName,
            $lifetimeSeconds,
        ): AuthPlatformSessionModel {
            $now = $this->clock->now();

            return $this->sessions->newQuery()->create([
                'public_id' => (string) Str::uuid(),
                'user_id' => $userId,
                'status' => 'active',
                'ip_address' => $this->nullableString($ipAddress),
                'user_agent' => $this->truncate($userAgent, 1024),
                'device_name' => $this->truncate($deviceName, 160),
                'authenticated_at' => $now,
                'last_activity_at' => $now,
                'expires_at' => $now->modify('+'.max(1, $lifetimeSeconds).' seconds'),
                'row_version' => 1,
            ]);
        });
    }

    public function page(?int $userId, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->executionContext->runAsControlPlane(function () use ($userId, $page, $perPage): LengthAwarePaginator {
            return $this->sessions->newQuery()
                ->with('user:id,first_name,last_name,platform_login_email,status')
                ->when($userId !== null, fn (Builder $query) => $query->where('user_id', $userId))
                ->orderByDesc('last_activity_at')
                ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    public function revoke(string $publicId, string $reason): AuthPlatformSessionModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($publicId, $reason): AuthPlatformSessionModel {
            return $this->database->transaction(function () use ($publicId, $reason): AuthPlatformSessionModel {
                $session = $this->sessions->newQuery()
                    ->where('public_id', trim($publicId))
                    ->lockForUpdate()
                    ->first();
                if (! $session instanceof AuthPlatformSessionModel) {
                    throw (new ModelNotFoundException())->setModel(AuthPlatformSessionModel::class, [$publicId]);
                }

                if ($session->getAttribute('status') === 'active') {
                    $this->revokeTokens((int) $session->getKey());
                    $session->forceFill([
                        'status' => 'revoked',
                        'revoked_at' => $this->clock->now(),
                        'row_version' => ((int) $session->getAttribute('row_version')) + 1,
                    ])->save();
                }

                $this->recordAudit('session_revoked', $session, $reason);

                return $session->fresh(['user']) ?? $session;
            }, 3);
        });
    }

    public function revokeAllForOperator(int $operatorId, string $reason): int
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $reason): int {
            return $this->database->transaction(
                fn (): int => $this->revokeAllLocked($operatorId, $reason),
                3,
            );
        });
    }

    public function resetMfa(int $operatorId, string $reason): void
    {
        $this->executionContext->runAsControlPlane(function () use ($operatorId, $reason): void {
            $this->database->transaction(function () use ($operatorId, $reason): void {
                $operator = $this->assertPlatformOperator($operatorId, true);
                $this->mfaMethods->newQuery()
                    ->where('user_id', $operatorId)
                    ->lockForUpdate()
                    ->get()
                    ->each(static fn (AuthPlatformMfaMethodModel $method): bool => (bool) $method->delete());

                $this->revokeAllLocked($operatorId, 'MFA reset: '.$reason, false);

                $this->audit->recordPlatform(new AuditEventData(
                    eventName: 'platform.operator.mfa_reset',
                    eventCategory: AuditEventCategory::SECURITY,
                    sourceModule: 'auth',
                    subjectType: 'platform_operator',
                    subjectId: (string) $operatorId,
                    subjectReference: (string) $operator->getAttribute('platform_login_email'),
                    changes: ['new' => ['mfa_status' => 'not_enrolled']],
                    metadata: ['reason' => $reason],
                    tags: ['platform', 'operator', 'mfa', 'security'],
                ));
            }, 3);
        });
    }

    private function revokeAllLocked(int $operatorId, string $reason, bool $assertOperator = true): int
    {
        if ($assertOperator) {
            $this->assertPlatformOperator($operatorId, true);
        }

        $sessions = $this->sessions->newQuery()
            ->where('user_id', $operatorId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();

        foreach ($sessions as $session) {
            if (! $session instanceof AuthPlatformSessionModel) {
                continue;
            }

            $this->revokeTokens((int) $session->getKey());
            $session->forceFill([
                'status' => 'revoked',
                'revoked_at' => $this->clock->now(),
                'row_version' => ((int) $session->getAttribute('row_version')) + 1,
            ])->save();
            $this->recordAudit('session_revoked', $session, $reason);
        }

        return $sessions->count();
    }

    private function revokeTokens(int $sessionId): void
    {
        $now = $this->clock->now();
        $this->accessTokens->newQuery()
            ->where('platform_session_id', $sessionId)
            ->where('status', 'active')
            ->update(['status' => 'revoked', 'revoked_at' => $now, 'updated_at' => $now]);
        $this->refreshTokens->newQuery()
            ->where('platform_session_id', $sessionId)
            ->where('status', 'active')
            ->update(['status' => 'revoked', 'revoked_at' => $now, 'updated_at' => $now]);
    }

    private function assertPlatformOperator(int $operatorId, bool $lock = false): UserModel
    {
        $query = $this->users->newQuery()
            ->whereKey($operatorId)
            ->whereNull('tenant_id')
            ->where('is_platform_operator', true)
            ->whereNull('deleted_at');
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw (new ModelNotFoundException())->setModel(UserModel::class, [$operatorId]);
    }

    private function recordAudit(string $action, AuthPlatformSessionModel $session, string $reason): void
    {
        $this->audit->recordPlatform(new AuditEventData(
            eventName: "platform.{$action}",
            eventCategory: AuditEventCategory::SECURITY,
            sourceModule: 'auth',
            subjectType: 'platform_session',
            subjectId: (string) $session->getAttribute('public_id'),
            subjectReference: (string) $session->getAttribute('public_id'),
            changes: ['new' => ['status' => $session->getAttribute('status')]],
            metadata: [
                'operator_id' => (int) $session->getAttribute('user_id'),
                'reason' => trim($reason),
                'performed_by' => $this->currentUser->currentUserId(),
            ],
            tags: ['platform', 'session', 'security'],
        ));
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function truncate(?string $value, int $max): ?string
    {
        $value = $this->nullableString($value);

        return $value === null ? null : mb_substr($value, 0, $max);
    }
}
