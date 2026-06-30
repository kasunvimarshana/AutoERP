<?php

declare(strict_types=1);

namespace Modules\User\Services\Platform\Invitations;

use Illuminate\Contracts\Encryption\DecryptException;
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
use Modules\Core\Http\Middleware\RequestCorrelationIdMiddleware;
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
    private const REPLACED_INVITATION_REASON = 'Replaced by a new invitation.';
    private const REPLACED_DELIVERY_REASON = 'Superseded by a newer delivery attempt for the same invitation.';
    private const TOKEN_REPLACEMENT_REASON = 'The invitation token could not be safely redelivered and was replaced.';

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
        $this->revokePendingInvitations((int) $operator->getKey(), self::REPLACED_INVITATION_REASON);

        $token = $this->tokens->issue();
        $invitation = $this->invitations->newQuery()->create([
            'public_id' => (string) Str::uuid(),
            'platform_operator_id' => (int) $operator->getKey(),
            'created_by_operator_id' => $this->currentUser->currentUserId(),
            'token_hash' => $this->tokens->digest($token),
            'delivery_token' => $token,
            'status' => PlatformOperatorInvitationStatus::PENDING,
            'expires_at' => $this->invitationExpiry(),
            'row_version' => 1,
        ]);
        $delivery = $this->createDelivery($invitation, 1);
        $this->dispatchDelivery($invitation, $delivery);

        return $invitation;
    }

    public function resend(int $operatorId, int $expectedVersion): PlatformOperatorModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $expectedVersion): PlatformOperatorModel {
            return $this->database->transaction(function () use ($operatorId, $expectedVersion): PlatformOperatorModel {
                $invitation = $this->latestPendingInvitation($operatorId);
                $operator = $this->operator($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                $this->assertInvitable($operator);

                if ($invitation instanceof PlatformOperatorInvitationModel && $this->canRedeliver($invitation)) {
                    $this->queueRedelivery($invitation);
                } else {
                    if ($invitation instanceof PlatformOperatorInvitationModel) {
                        $this->retireUnavailableInvitation($invitation);
                    }
                    $this->issueForOperator($operator);
                }

                $now = $this->clock->now();
                $operator->forceFill([
                    'invited_at' => $now,
                    'row_version' => $expectedVersion + 1,
                    'updated_by_operator_id' => $this->currentUser->currentUserId(),
                    'updated_at' => $now,
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
                $pendingInvitations = $this->pendingInvitations($operatorId);
                $operator = $this->operator($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                $this->assertInvitable($operator);
                $this->revokeLockedInvitations($pendingInvitations, $reason);
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

        $query = $this->invitations->newQuery()
            ->with('operator')
            ->whereIn('token_hash', $this->tokens->lookupDigests($plainToken));
        if ($lock) {
            $query->lockForUpdate();
        }
        $invitation = $query->first();
        if (! $invitation instanceof PlatformOperatorInvitationModel) {
            $this->logTokenLookupMiss($plainToken);
            throw ValidationException::withMessages(['token' => ['This invitation is invalid or no longer available.']]);
        }

        $status = (string) $invitation->getAttribute('status');
        if ($status === PlatformOperatorInvitationStatus::ACCEPTED) {
            throw ValidationException::withMessages(['token' => ['This invitation has already been used. Return to sign in.']]);
        }
        if ($status === PlatformOperatorInvitationStatus::REVOKED) {
            $reason = (string) $invitation->getAttribute('revocation_reason');
            $message = str_contains(strtolower($reason), 'replaced')
                ? 'This invitation was replaced. Use the most recent invitation email.'
                : 'This invitation was revoked. Ask a platform manager to send a new one.';
            throw ValidationException::withMessages(['token' => [$message]]);
        }
        if ($status === PlatformOperatorInvitationStatus::EXPIRED) {
            throw ValidationException::withMessages(['token' => ['This invitation has expired. Ask a platform manager to send a new one.']]);
        }
        if ($status !== PlatformOperatorInvitationStatus::PENDING) {
            throw ValidationException::withMessages(['token' => ['This invitation is no longer available.']]);
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

    private function latestPendingInvitation(int $operatorId): ?PlatformOperatorInvitationModel
    {
        return $this->invitations->newQuery()
            ->where('platform_operator_id', $operatorId)
            ->where('status', PlatformOperatorInvitationStatus::PENDING)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->last();
    }

    /** @return list<PlatformOperatorInvitationModel> */
    private function pendingInvitations(int $operatorId, ?int $exceptInvitationId = null): array
    {
        $query = $this->invitations->newQuery()
            ->where('platform_operator_id', $operatorId)
            ->where('status', PlatformOperatorInvitationStatus::PENDING)
            ->orderBy('id')
            ->lockForUpdate();
        if ($exceptInvitationId !== null) {
            $query->where('id', '!=', $exceptInvitationId);
        }

        return $query->get()->all();
    }

    private function canRedeliver(PlatformOperatorInvitationModel $invitation): bool
    {
        if ($invitation->getAttribute('expires_at')->toImmutable() <= $this->clock->now()) {
            return false;
        }

        try {
            $token = trim((string) $invitation->getAttribute('delivery_token'));
        } catch (DecryptException) {
            return false;
        }

        return $token !== ''
            && $this->tokens->matchesCurrentDigest(
                $token,
                (string) $invitation->getAttribute('token_hash'),
            );
    }

    private function queueRedelivery(PlatformOperatorInvitationModel $invitation): void
    {
        $latestDelivery = $this->deliveries->newQuery()
            ->where('invitation_id', $invitation->getKey())
            ->orderByDesc('attempt_number')
            ->lockForUpdate()
            ->first();
        $nextAttempt = $latestDelivery instanceof PlatformOperatorInvitationDeliveryModel
            ? (int) $latestDelivery->getAttribute('attempt_number') + 1
            : 1;

        $this->cancelDeliveries((int) $invitation->getKey(), self::REPLACED_DELIVERY_REASON);
        $invitation->forceFill([
            'expires_at' => $this->invitationExpiry(),
            'row_version' => (int) $invitation->getAttribute('row_version') + 1,
            'updated_at' => $this->clock->now(),
        ])->save();

        $delivery = $this->createDelivery($invitation, $nextAttempt);
        $this->dispatchDelivery($invitation, $delivery);
    }

    private function retireUnavailableInvitation(PlatformOperatorInvitationModel $invitation): void
    {
        $expired = $invitation->getAttribute('expires_at')->toImmutable() <= $this->clock->now();
        $reason = $expired ? 'Invitation expired.' : self::TOKEN_REPLACEMENT_REASON;
        $invitation->forceFill([
            'status' => $expired
                ? PlatformOperatorInvitationStatus::EXPIRED
                : PlatformOperatorInvitationStatus::REVOKED,
            'revoked_at' => $expired ? null : $this->clock->now(),
            'revocation_reason' => $expired ? null : $reason,
            'delivery_token' => null,
            'row_version' => (int) $invitation->getAttribute('row_version') + 1,
            'updated_at' => $this->clock->now(),
        ])->save();
        $this->cancelDeliveries((int) $invitation->getKey(), $reason);
    }

    private function createDelivery(
        PlatformOperatorInvitationModel $invitation,
        int $attemptNumber,
    ): PlatformOperatorInvitationDeliveryModel {
        return $this->deliveries->newQuery()->create([
            'invitation_id' => $invitation->getKey(),
            'attempt_number' => $attemptNumber,
            'status' => PlatformOperatorInvitationDeliveryStatus::QUEUED,
            'row_version' => 1,
        ]);
    }

    private function dispatchDelivery(
        PlatformOperatorInvitationModel $invitation,
        PlatformOperatorInvitationDeliveryModel $delivery,
    ): void {
        DeliverPlatformOperatorInvitation::dispatch(
            (int) $invitation->getKey(),
            (int) $delivery->getKey(),
        )->afterCommit();
    }

    private function invitationExpiry(): \DateTimeImmutable
    {
        return $this->clock->now()->modify(sprintf(
            '+%d minutes',
            max(15, (int) config('user.platform.operator_invitation_ttl_minutes', 1440)),
        ));
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
        $this->revokeLockedInvitations(
            $this->pendingInvitations($operatorId, $exceptInvitationId),
            $reason,
        );
    }

    /** @param list<PlatformOperatorInvitationModel> $pendingInvitations */
    private function revokeLockedInvitations(array $pendingInvitations, string $reason): void
    {
        foreach ($pendingInvitations as $invitation) {
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

    private function logTokenLookupMiss(string $plainToken): void
    {
        $correlationId = null;
        $host = null;
        if (app()->bound('request')) {
            $request = request();
            $attribute = $request->attributes->get(RequestCorrelationIdMiddleware::ATTRIBUTE);
            $correlationId = is_string($attribute) ? $attribute : null;
            $host = $request->getHost();
        }

        logger()->notice('Platform operator invitation token lookup failed.', [
            'correlation_id' => $correlationId,
            'request_host' => $host,
            'current_digest_prefix' => substr($this->tokens->digest($plainToken), 0, 12),
            'legacy_digest_prefix' => substr($this->tokens->legacyDigest($plainToken), 0, 12),
            'database_connection' => (string) config('database.default'),
            'application_environment' => app()->environment(),
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
