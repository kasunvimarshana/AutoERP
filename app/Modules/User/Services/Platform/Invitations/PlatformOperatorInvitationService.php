<?php

declare(strict_types=1);

namespace Modules\User\Services\Platform\Invitations;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\PlatformAuditActorData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorInvitationDeliveryStatus;
use Modules\User\Constants\PlatformOperatorInvitationStatus;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Contracts\PlatformMfaEnrollmentIssuerInterface;
use Modules\User\Contracts\PlatformOperatorCredentialProvisionerInterface;
use Modules\User\Jobs\DeliverPlatformOperatorInvitation;
use Modules\User\Models\PlatformOperatorInvitationDeliveryModel;
use Modules\User\Models\PlatformOperatorInvitationModel;
use Modules\User\Models\PlatformOperatorModel;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PlatformOperatorInvitationService
{
    public function __construct(
        private readonly PlatformOperatorInvitationModel $invitations,
        private readonly PlatformOperatorInvitationDeliveryModel $deliveries,
        private readonly PlatformOperatorModel $operators,
        private readonly PlatformOperatorCredentialProvisionerInterface $credentials,
        private readonly PlatformMfaEnrollmentIssuerInterface $mfaEnrollment,
        private readonly PlatformOperatorInvitationTokenCodec $tokens,
        private readonly ClockInterface $clock,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly DatabaseManager $database,
        private readonly AuditRecorderInterface $audit,
    ) {}

    public function issueForOperator(PlatformOperatorModel $operator): PlatformOperatorInvitationModel
    {
        $this->assertInvitable($operator);
        $this->revokePendingInvitations((int) $operator->getKey(), 'Replaced by a new invitation.');

        $token = $this->tokens->issue();
        $invitation = $this->invitations->newQuery()->create([
            'public_id' => (string) Str::uuid(),
            'platform_operator_id' => (int) $operator->getKey(),
            'created_by_operator_id' => $this->currentUser->currentUserId(),
            'token_hash' => $this->tokens->digest($token),
            'delivery_token' => $token,
            'status' => PlatformOperatorInvitationStatus::PENDING,
            'expires_at' => $this->clock->now()->modify(sprintf(
                '+%d minutes',
                max(15, (int) config('user.platform.operator_invitation_ttl_minutes', 1440)),
            )),
            'row_version' => 1,
        ]);
        $this->deliveries->newQuery()->create([
            'invitation_id' => $invitation->getKey(),
            'attempt_number' => 1,
            'status' => PlatformOperatorInvitationDeliveryStatus::QUEUED,
            'row_version' => 1,
        ]);
        DeliverPlatformOperatorInvitation::dispatch((int) $invitation->getKey())->afterCommit();

        return $invitation;
    }

    public function resend(int $operatorId, int $expectedVersion): PlatformOperatorModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $expectedVersion): PlatformOperatorModel {
            return $this->database->transaction(function () use ($operatorId, $expectedVersion): PlatformOperatorModel {
                $operator = $this->operator($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                $this->assertInvitable($operator);
                $this->issueForOperator($operator);
                $operator->forceFill([
                    'invited_at' => $this->clock->now(),
                    'row_version' => $expectedVersion + 1,
                    'updated_by_operator_id' => $this->currentUser->currentUserId(),
                    'updated_at' => $this->clock->now(),
                ])->save();
                $this->recordAudit('invitation_resent', $operator);

                return $this->reload($operator);
            }, 3);
        });
    }

    public function revoke(int $operatorId, int $expectedVersion, string $reason): PlatformOperatorModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $expectedVersion, $reason): PlatformOperatorModel {
            $reason = trim($reason);
            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => ['A reason is required.']]);
            }

            return $this->database->transaction(function () use ($operatorId, $expectedVersion, $reason): PlatformOperatorModel {
                $operator = $this->operator($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                $this->assertInvitable($operator);
                $this->revokePendingInvitations($operatorId, $reason);
                $operator->forceFill([
                    'status' => PlatformOperatorStatus::INACTIVE,
                    'deactivated_at' => $this->clock->now(),
                    'row_version' => $expectedVersion + 1,
                    'updated_by_operator_id' => $this->currentUser->currentUserId(),
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
            if (! $operator instanceof PlatformOperatorModel || $operator->getAttribute('status') !== PlatformOperatorStatus::INVITED) {
                throw ValidationException::withMessages(['token' => ['This invitation is no longer available.']]);
            }
            $latestDelivery = $invitation->deliveries()->latest('attempt_number')->first();

            return [
                'operator_name' => trim((string) $operator->getAttribute('first_name').' '.(string) $operator->getAttribute('last_name')),
                'email' => (string) $operator->getAttribute('email'),
                'expires_at' => $invitation->getAttribute('expires_at')->toAtomString(),
                'delivery_status' => $latestDelivery?->getAttribute('status'),
                'password_policy' => $this->credentials->passwordRequirements(),
            ];
        });
    }

    /** @return array<string,mixed> */
    public function accept(string $plainToken, string $password): array
    {
        return $this->executionContext->runAsControlPlane(function () use ($plainToken, $password): array {
            return $this->database->transaction(function () use ($plainToken, $password): array {
                $invitation = $this->pendingByToken($plainToken, true);
                $operator = $this->operator((int) $invitation->getAttribute('platform_operator_id'), true);
                if ($operator->getAttribute('status') !== PlatformOperatorStatus::INVITED) {
                    throw ValidationException::withMessages(['token' => ['This invitation is no longer available.']]);
                }

                $now = $this->clock->now();
                $this->credentials->provision((int) $operator->getKey(), $password);
                $operator->forceFill([
                    'credentials_ready_at' => $now,
                    'status' => PlatformOperatorStatus::ACTIVE,
                    'activated_at' => $now,
                    'deactivated_at' => null,
                    'row_version' => (int) $operator->getAttribute('row_version') + 1,
                    'updated_at' => $now,
                ])->save();
                $invitation->forceFill([
                    'status' => PlatformOperatorInvitationStatus::ACCEPTED,
                    'accepted_at' => $now,
                    'delivery_token' => null,
                    'row_version' => (int) $invitation->getAttribute('row_version') + 1,
                    'updated_at' => $now,
                ])->save();
                $this->revokePendingInvitations((int) $operator->getKey(), 'Operator registration completed.', (int) $invitation->getKey());
                $this->recordAcceptanceAudit($operator);

                $email = (string) $operator->getAttribute('email');
                $enrollment = $this->mfaEnrollment->issueForOperator((int) $operator->getKey(), $email);

                return [
                    'operator_name' => trim((string) $operator->getAttribute('first_name').' '.(string) $operator->getAttribute('last_name')),
                    'email' => $email,
                    'status' => PlatformOperatorStatus::ACTIVE,
                    'mfa_enrollment' => $enrollment,
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
        $query = $this->invitations->newQuery()->with('operator')
            ->where('token_hash', $this->tokens->digest($plainToken))
            ->where('status', PlatformOperatorInvitationStatus::PENDING);
        if ($lock) {
            $query->lockForUpdate();
        }
        $invitation = $query->first();
        if (! $invitation instanceof PlatformOperatorInvitationModel) {
            throw ValidationException::withMessages(['token' => ['This invitation is invalid or no longer available.']]);
        }
        if ($invitation->getAttribute('expires_at')->toImmutable() <= $this->clock->now()) {
            if ($lock) {
                $invitation->forceFill([
                    'status' => PlatformOperatorInvitationStatus::EXPIRED,
                    'delivery_token' => null,
                    'row_version' => (int) $invitation->getAttribute('row_version') + 1,
                ])->save();
                $this->cancelDeliveries((int) $invitation->getKey(), 'Invitation expired.');
            }
            throw ValidationException::withMessages(['token' => ['This invitation has expired. Ask a platform manager to send a new one.']]);
        }

        return $invitation;
    }

    private function operator(int $operatorId, bool $lock): PlatformOperatorModel
    {
        $query = $this->operators->newQuery()->whereKey($operatorId);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw (new ModelNotFoundException())->setModel(PlatformOperatorModel::class, [$operatorId]);
    }

    private function reload(PlatformOperatorModel $operator): PlatformOperatorModel
    {
        return $this->operators->newQuery()->with(['permissionAssignments.permission', 'latestInvitation.deliveries'])
            ->whereKey($operator->getKey())->firstOrFail();
    }

    private function assertInvitable(PlatformOperatorModel $operator): void
    {
        if ($operator->getAttribute('status') !== PlatformOperatorStatus::INVITED) {
            throw new ConflictHttpException('Only invited platform operators can receive or revoke registration invitations.');
        }
    }

    private function assertVersion(PlatformOperatorModel $operator, int $expectedVersion): void
    {
        if ((int) $operator->getAttribute('row_version') !== $expectedVersion) {
            throw new ConflictHttpException('The platform operator changed after it was loaded. Refresh and try again.');
        }
    }

    private function revokePendingInvitations(int $operatorId, string $reason, ?int $exceptInvitationId = null): void
    {
        $query = $this->invitations->newQuery()->where('platform_operator_id', $operatorId)
            ->where('status', PlatformOperatorInvitationStatus::PENDING)->lockForUpdate();
        if ($exceptInvitationId !== null) {
            $query->where('id', '!=', $exceptInvitationId);
        }
        /** @var list<PlatformOperatorInvitationModel> $pending */
        $pending = $query->get()->all();
        foreach ($pending as $invitation) {
            $invitation->forceFill([
                'status' => PlatformOperatorInvitationStatus::REVOKED,
                'revoked_at' => $this->clock->now(),
                'revocation_reason' => $reason,
                'delivery_token' => null,
                'row_version' => (int) $invitation->getAttribute('row_version') + 1,
            ])->save();
            $this->cancelDeliveries((int) $invitation->getKey(), $reason);
        }
    }

    private function cancelDeliveries(int $invitationId, string $reason): void
    {
        $this->deliveries->newQuery()->where('invitation_id', $invitationId)
            ->whereIn('status', [
                PlatformOperatorInvitationDeliveryStatus::QUEUED,
                PlatformOperatorInvitationDeliveryStatus::SENDING,
                PlatformOperatorInvitationDeliveryStatus::FAILED,
            ])->increment('row_version', 1, [
                'status' => PlatformOperatorInvitationDeliveryStatus::CANCELLED,
                'claim_token' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
                'error_code' => 'PLATFORM_OPERATOR_INVITATION_CANCELLED',
                'error_message' => $reason,
                'updated_at' => $this->clock->now(),
            ]);
    }

    private function recordAcceptanceAudit(PlatformOperatorModel $operator): void
    {
        $name = trim((string) $operator->getAttribute('first_name').' '.(string) $operator->getAttribute('last_name'));
        $email = (string) $operator->getAttribute('email');

        $this->audit->recordPlatformActor(
            new AuditEventData(
                eventName: 'platform.operator.invitation_accepted',
                eventCategory: AuditEventCategory::SECURITY,
                sourceModule: 'user',
                subjectType: 'platform_operator',
                subjectId: (string) $operator->getKey(),
                subjectReference: $email,
                tags: ['platform', 'operator', 'invitation'],
            ),
            new PlatformAuditActorData(
                actorType: AuditActorType::USER,
                actorId: (string) $operator->getKey(),
                actorName: $name !== '' ? $name : $email,
                actorGuard: 'platform-invitation',
            ),
        );
    }

    private function recordAudit(string $action, PlatformOperatorModel $operator, ?string $reason = null): void
    {
        $this->audit->recordPlatform(new AuditEventData(
            eventName: "platform.operator.{$action}",
            eventCategory: AuditEventCategory::SECURITY,
            sourceModule: 'user',
            subjectType: 'platform_operator',
            subjectId: (string) $operator->getKey(),
            subjectReference: (string) $operator->getAttribute('email'),
            metadata: $reason === null ? [] : ['reason' => $reason],
            tags: ['platform', 'operator', 'invitation'],
        ));
    }
}
