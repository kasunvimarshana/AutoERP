<?php

declare(strict_types=1);

namespace Modules\User\Services\Platform\Invitations;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorInvitationDeliveryStatus;
use Modules\User\Constants\PlatformOperatorInvitationStatus;
use Modules\User\Constants\UserStatus;
use Modules\User\Jobs\DeliverPlatformOperatorInvitation;
use Modules\User\Models\PlatformOperatorInvitationModel;
use Modules\User\Models\UserModel;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PlatformOperatorInvitationService
{
    public function __construct(
        private readonly PlatformOperatorInvitationModel $invitations,
        private readonly UserModel $users,
        private readonly PasswordHasherInterface $passwords,
        private readonly ClockInterface $clock,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly DatabaseManager $database,
        private readonly AuditRecorderInterface $audit,
    ) {}

    public function issueForOperator(UserModel $operator): PlatformOperatorInvitationModel
    {
        $this->assertInvitable($operator);
        $this->revokePendingInvitations((int) $operator->getKey(), 'Replaced by a new invitation.');

        $token = Str::random(72);
        $invitation = $this->invitations->newQuery()->create([
            'public_id' => (string) Str::uuid(),
            'user_id' => (int) $operator->getKey(),
            'created_by_user_id' => $this->currentUser->currentUserId(),
            'token_hash' => hash('sha256', $token),
            'delivery_token' => $token,
            'status' => PlatformOperatorInvitationStatus::PENDING,
            'delivery_status' => PlatformOperatorInvitationDeliveryStatus::QUEUED,
            'processing_attempt_count' => 0,
            'expires_at' => $this->clock->now()->modify(sprintf(
                '+%d minutes',
                max(15, (int) config('user.platform.operator_invitation_ttl_minutes', 1440)),
            )),
            'row_version' => 1,
        ]);

        DeliverPlatformOperatorInvitation::dispatch((int) $invitation->getKey())->afterCommit();

        return $invitation;
    }

    public function resend(int $operatorId, int $expectedVersion): UserModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $expectedVersion): UserModel {
            return $this->database->transaction(function () use ($operatorId, $expectedVersion): UserModel {
                $operator = $this->operator($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                $this->assertInvitable($operator);
                $this->issueForOperator($operator);
                $operator->forceFill([
                    'row_version' => $expectedVersion + 1,
                    'updated_at' => $this->clock->now(),
                ])->save();
                $this->recordAudit('invitation_resent', $operator);

                return $this->reload($operator);
            }, 3);
        });
    }

    public function revoke(int $operatorId, int $expectedVersion, string $reason): UserModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $expectedVersion, $reason): UserModel {
            $reason = trim($reason);
            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => ['A reason is required.']]);
            }

            return $this->database->transaction(function () use ($operatorId, $expectedVersion, $reason): UserModel {
                $operator = $this->operator($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                $this->assertInvitable($operator);
                $this->revokePendingInvitations($operatorId, $reason);
                $operator->forceFill([
                    'status' => UserStatus::INACTIVE,
                    'row_version' => $expectedVersion + 1,
                    'updated_at' => $this->clock->now(),
                ])->save();
                $this->recordAudit('invitation_revoked', $operator, $reason);

                return $this->reload($operator);
            }, 3);
        });
    }

    /** @return array<string,mixed> */
    public function inspect(string $plainToken): array
    {
        return $this->executionContext->runAsControlPlane(function () use ($plainToken): array {
            $invitation = $this->pendingByToken($plainToken, false);
            $operator = $invitation->operator;
            if (! $operator instanceof UserModel || $operator->getAttribute('status') !== UserStatus::INVITED) {
                throw ValidationException::withMessages(['token' => ['This invitation is no longer available.']]);
            }

            return [
                'operator_name' => trim((string) $operator->getAttribute('first_name').' '.(string) $operator->getAttribute('last_name')),
                'email' => (string) $operator->getAttribute('platform_login_email'),
                'expires_at' => $invitation->getAttribute('expires_at')->toAtomString(),
                'delivery_status' => (string) $invitation->getAttribute('delivery_status'),
            ];
        });
    }

    /** @return array<string,mixed> */
    public function accept(string $plainToken, string $password): array
    {
        return $this->executionContext->runAsControlPlane(function () use ($plainToken, $password): array {
            return $this->database->transaction(function () use ($plainToken, $password): array {
                $invitation = $this->pendingByToken($plainToken, true);
                $operator = $this->operator((int) $invitation->getAttribute('user_id'), true);
                if ($operator->getAttribute('status') !== UserStatus::INVITED) {
                    throw ValidationException::withMessages(['token' => ['This invitation is no longer available.']]);
                }

                $now = $this->clock->now();
                $operator->forceFill([
                    'password' => $this->passwords->hash($password),
                    'email_verified_at' => $now,
                    'status' => UserStatus::ACTIVE,
                    'row_version' => (int) $operator->getAttribute('row_version') + 1,
                    'updated_at' => $now,
                ])->save();
                $invitation->forceFill([
                    'status' => PlatformOperatorInvitationStatus::ACCEPTED,
                    'accepted_at' => $now,
                    'delivery_token' => null,
                    'claim_token' => null,
                    'claimed_at' => null,
                    'lease_expires_at' => null,
                    'row_version' => (int) $invitation->getAttribute('row_version') + 1,
                    'updated_at' => $now,
                ])->save();
                $this->revokePendingInvitations((int) $operator->getKey(), 'Operator registration completed.', (int) $invitation->getKey());
                $this->recordAudit('invitation_accepted', $operator);

                return [
                    'operator_name' => trim((string) $operator->getAttribute('first_name').' '.(string) $operator->getAttribute('last_name')),
                    'email' => (string) $operator->getAttribute('platform_login_email'),
                    'status' => UserStatus::ACTIVE,
                ];
            }, 3);
        });
    }

    private function pendingByToken(string $plainToken, bool $lock): PlatformOperatorInvitationModel
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '') {
            throw ValidationException::withMessages(['token' => ['A valid invitation token is required.']]);
        }

        $query = $this->invitations->newQuery()
            ->with('operator')
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('status', PlatformOperatorInvitationStatus::PENDING);
        if ($lock) {
            $query->lockForUpdate();
        }

        $invitation = $query->first();
        if (! $invitation instanceof PlatformOperatorInvitationModel) {
            throw ValidationException::withMessages(['token' => ['This invitation is invalid or no longer available.']]);
        }
        if ($invitation->getAttribute('expires_at')->toImmutable() <= $this->clock->now()) {
            if (! $lock) {
                throw ValidationException::withMessages(['token' => ['This invitation has expired. Ask a platform manager to send a new one.']]);
            }

            $invitation->forceFill([
                'status' => PlatformOperatorInvitationStatus::EXPIRED,
                'delivery_status' => PlatformOperatorInvitationDeliveryStatus::CANCELLED,
                'delivery_token' => null,
                'row_version' => (int) $invitation->getAttribute('row_version') + 1,
            ])->save();

            throw ValidationException::withMessages(['token' => ['This invitation has expired. Ask a platform manager to send a new one.']]);
        }

        return $invitation;
    }

    private function operator(int $operatorId, bool $lock): UserModel
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

    private function reload(UserModel $operator): UserModel
    {
        return $this->users->newQuery()
            ->whereKey($operator->getKey())
            ->with(['platformPermissionAssignments.permission', 'latestPlatformOperatorInvitation'])
            ->firstOrFail();
    }

    private function assertInvitable(UserModel $operator): void
    {
        if ($operator->getAttribute('status') !== UserStatus::INVITED) {
            throw new ConflictHttpException('Only an invited platform operator can use this invitation operation.');
        }
    }

    private function assertVersion(UserModel $operator, int $expectedVersion): void
    {
        if ((int) $operator->getAttribute('row_version') !== $expectedVersion) {
            throw new ConflictHttpException('The platform operator changed after it was loaded. Refresh and try again.');
        }
    }

    private function revokePendingInvitations(int $operatorId, string $reason, ?int $exceptId = null): void
    {
        $query = $this->invitations->newQuery()
            ->where('user_id', $operatorId)
            ->where('status', PlatformOperatorInvitationStatus::PENDING);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $now = $this->clock->now();
        /** @var list<PlatformOperatorInvitationModel> $pending */
        $pending = $query->lockForUpdate()->get()->all();
        foreach ($pending as $invitation) {
            $invitation->forceFill([
                'status' => PlatformOperatorInvitationStatus::REVOKED,
                'delivery_status' => PlatformOperatorInvitationDeliveryStatus::CANCELLED,
                'delivery_token' => null,
                'revoked_at' => $now,
                'claim_token' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
                'error_code' => 'PLATFORM_OPERATOR_INVITATION_REVOKED',
                'error_message' => mb_substr($reason, 0, 1000),
                'row_version' => (int) $invitation->getAttribute('row_version') + 1,
                'updated_at' => $now,
            ])->save();
        }
    }

    private function recordAudit(string $action, UserModel $operator, ?string $reason = null): void
    {
        $this->audit->recordPlatform(new AuditEventData(
            eventName: "platform.operator.{$action}",
            eventCategory: AuditEventCategory::SECURITY,
            sourceModule: 'user',
            subjectType: 'platform_operator',
            subjectId: (string) $operator->getKey(),
            subjectReference: (string) $operator->getAttribute('platform_login_email'),
            changes: ['new' => ['status' => $operator->getAttribute('status')]],
            metadata: $reason === null ? [] : ['reason' => $reason],
            tags: ['platform', 'operator', 'invitation', 'security'],
        ));
    }
}
