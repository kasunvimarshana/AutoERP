<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use DateInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Constants\InvitationDeliveryStatus;
use Modules\Auth\Constants\RegistrationInvitationPurpose;
use Modules\Auth\Constants\RegistrationInvitationStatus;
use Modules\Auth\Jobs\DeliverRegistrationInvitation;
use Modules\Auth\Models\AuthRegistrationInvitationDeliveryModel;
use Modules\Auth\Models\AuthRegistrationInvitationModel;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantDirectoryInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\TenantUserInvitationIssuerInterface;
use RuntimeException;

final class RegistrationInvitationService implements TenantUserInvitationIssuerInterface
{
    private const DEFAULT_EXPIRY_HOURS = 72;
    private const REPLACED_REASON = 'Replaced by a newer initial administrator invitation.';
    private const REVOKED_DELIVERY_REASON = 'The invitation was revoked before delivery completed.';

    public function __construct(
        private readonly AuthRegistrationInvitationModel $invitations,
        private readonly AuthRegistrationInvitationDeliveryModel $deliveries,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly ClockInterface $clock,
        private readonly OpaqueTokenCodec $tokens,
        private readonly TenantDirectoryInterface $tenants,
    ) {}

    /** @return array{invitation_id:int,expires_at:string,delivery_status:string} */
    public function issueForUser(int $tenantId, int $userId, string $email): array
    {
        $email = strtolower(trim($email));
        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = $this->invitationExpiry();

        [$invitation, $delivery] = DB::transaction(function () use ($tenantId, $userId, $email, $plainToken, $expiresAt): array {
            $pendingInvitations = $this->invitations->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('purpose', RegistrationInvitationPurpose::USER_INVITATION)
                ->where('status', RegistrationInvitationStatus::PENDING)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($pendingInvitations as $pendingInvitation) {
                $this->revokeLocked($pendingInvitation, 'Replaced by a newer user invitation.');
            }

            $invitation = $this->invitations->newQuery()->create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'organization_unit_id' => null,
                'role_id' => null,
                'email' => $email,
                'token_hash' => $this->tokens->digestArbitrary($plainToken, 'registration-invitation'),
                'delivery_token' => $plainToken,
                'purpose' => RegistrationInvitationPurpose::USER_INVITATION,
                'status' => RegistrationInvitationStatus::PENDING,
                'expires_at' => $expiresAt,
                'row_version' => 1,
            ]);
            $delivery = $this->createDelivery($invitation, 1);

            return [$invitation, $delivery];
        }, 3);

        $this->dispatchDelivery($tenantId, (int) $delivery->getKey());

        return [
            'invitation_id' => (int) $invitation->getKey(),
            'expires_at' => $expiresAt->format(DATE_ATOM),
            'delivery_status' => InvitationDeliveryStatus::QUEUED,
        ];
    }

    /** @return array{invitation_id:int,expires_at:string,delivery_status:string} */
    public function resendForUser(int $tenantId, int $userId): array
    {
        [$invitation, $delivery] = DB::transaction(function () use ($tenantId, $userId): array {
            $invitation = $this->invitations->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('purpose', RegistrationInvitationPurpose::USER_INVITATION)
                ->where('status', RegistrationInvitationStatus::PENDING)
                ->where('expires_at', '>', $this->clock->now())
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $invitation instanceof AuthRegistrationInvitationModel) {
                throw new RuntimeException('A pending user invitation was not found.');
            }
            if (trim((string) $invitation->getAttribute('delivery_token')) === '') {
                throw new RuntimeException('The invitation delivery token is no longer available. Issue a new invitation.');
            }

            $latestAttempt = (int) $this->deliveries->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('invitation_id', $invitation->getKey())
                ->lockForUpdate()
                ->max('attempt_number');
            $invitation->forceFill([
                'row_version' => (int) $invitation->getAttribute('row_version') + 1,
            ])->save();

            return [$invitation, $this->createDelivery($invitation, $latestAttempt + 1)];
        }, 3);

        $this->dispatchDelivery($tenantId, (int) $delivery->getKey());

        return [
            'invitation_id' => (int) $invitation->getKey(),
            'expires_at' => $invitation->getAttribute('expires_at')->toAtomString(),
            'delivery_status' => InvitationDeliveryStatus::QUEUED,
        ];
    }

    /** @return array{invitation_id:int,invitation_expires_at:string,delivery_status:string} */
    public function issueInitialAdministrator(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $email,
    ): array {
        $email = strtolower(trim($email));
        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = $this->invitationExpiry();

        [$invitation, $delivery] = DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $roleId,
            $email,
            $plainToken,
            $expiresAt,
        ): array {
            $existing = $this->invitations->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
                ->where('status', RegistrationInvitationStatus::PENDING)
                ->lockForUpdate()
                ->get();

            foreach ($existing as $pending) {
                $this->revokeLocked($pending, self::REPLACED_REASON);
            }

            $invitation = $this->invitations->newQuery()->create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'role_id' => $roleId,
                'email' => $email,
                'token_hash' => $this->tokens->digestArbitrary($plainToken, 'registration-invitation'),
                'delivery_token' => $plainToken,
                'purpose' => RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR,
                'status' => RegistrationInvitationStatus::PENDING,
                'expires_at' => $expiresAt,
                'row_version' => 1,
            ]);
            $delivery = $this->createDelivery($invitation, 1);

            return [$invitation, $delivery];
        }, 3);

        $this->dispatchDelivery($tenantId, (int) $delivery->getKey());

        return [
            'invitation_id' => (int) $invitation->getKey(),
            'invitation_expires_at' => $expiresAt->format(DATE_ATOM),
            'delivery_status' => InvitationDeliveryStatus::QUEUED,
        ];
    }

    public function findValid(int $tenantId, string $email, ?string $plainToken): ?array
    {
        $plainToken = trim((string) $plainToken);
        if ($plainToken === '') {
            return null;
        }

        $invitation = $this->invitations->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('email', strtolower(trim($email)))
            ->where('token_hash', $this->tokens->digestArbitrary($plainToken, 'registration-invitation'))
            ->where('status', RegistrationInvitationStatus::PENDING)
            ->where('expires_at', '>', $this->clock->now())
            ->lockForUpdate()
            ->first();

        return $invitation instanceof AuthRegistrationInvitationModel
            ? $invitation->attributesToArray()
            : null;
    }

    /** @return array{tenant_id:int,email:string,tenant_name:string,expires_at:string}|null */
    public function inspectInitialAdministratorToken(string $plainToken): ?array
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '') {
            return null;
        }

        return $this->executionContext->runAsControlPlane(function () use ($plainToken): ?array {
            $invitation = $this->invitations->newQuery()
                ->where('token_hash', $this->tokens->digestArbitrary($plainToken, 'registration-invitation'))
                ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
                ->where('status', RegistrationInvitationStatus::PENDING)
                ->where('expires_at', '>', $this->clock->now())
                ->first();

            if (! $invitation instanceof AuthRegistrationInvitationModel) {
                return null;
            }

            $tenantId = (int) $invitation->getAttribute('tenant_id');
            $tenant = $this->tenants->summary($tenantId);
            if ($tenant === null) {
                return null;
            }

            return [
                'tenant_id' => $tenantId,
                'email' => (string) $invitation->getAttribute('email'),
                'tenant_name' => $tenant['name'],
                'expires_at' => $invitation->getAttribute('expires_at')->toAtomString(),
            ];
        });
    }

    public function accept(int $tenantId, int $invitationId, int $userId, int $expectedVersion): void
    {
        DB::transaction(function () use ($tenantId, $invitationId, $userId, $expectedVersion): void {
            $invitation = $this->invitations->newQuery()
                ->whereKey($invitationId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if (
                ! $invitation instanceof AuthRegistrationInvitationModel
                || (int) $invitation->getAttribute('row_version') !== $expectedVersion
                || $invitation->getAttribute('status') !== RegistrationInvitationStatus::PENDING
                || $invitation->getAttribute('expires_at')->toImmutable() <= $this->clock->now()
            ) {
                throw new RuntimeException('Registration invitation changed or expired before it could be accepted.');
            }

            $invitation->forceFill([
                'status' => RegistrationInvitationStatus::ACCEPTED,
                'delivery_token' => null,
                'accepted_at' => $this->clock->now(),
                'accepted_by_user_id' => $userId,
                'row_version' => $expectedVersion + 1,
            ])->save();

            $this->cancelOpenDeliveries($tenantId, $invitationId, 'The invitation has already been accepted.');
        }, 3);
    }

    public function acceptedInitialAdministratorUserId(
        int $tenantId,
        ?int $invitationId = null,
        bool $lockForUpdate = false,
    ): ?int {
        $query = $this->invitations->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
            ->where('status', RegistrationInvitationStatus::ACCEPTED)
            ->whereNotNull('accepted_by_user_id');
        if ($invitationId !== null) {
            $query->whereKey($invitationId);
        }

        $userId = $query
            ->when($lockForUpdate, static fn ($query) => $query->lockForUpdate())
            ->orderByDesc('accepted_at')
            ->value('accepted_by_user_id');

        return is_numeric($userId) && (int) $userId > 0 ? (int) $userId : null;
    }

    public function hasPendingInitialAdministratorInvitation(
        int $tenantId,
        ?int $invitationId = null,
        bool $lockForUpdate = false,
    ): bool {
        $query = $this->invitations->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
            ->where('status', RegistrationInvitationStatus::PENDING)
            ->where('expires_at', '>', $this->clock->now());
        if ($invitationId !== null) {
            $query->whereKey($invitationId);
        }
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    /** @return array<string, mixed>|null */
    public function initialAdministratorStatus(
        int $tenantId,
        ?int $invitationId = null,
        bool $lockForUpdate = false,
    ): ?array {
        $query = $this->invitations->newQuery()
            ->with('latestDelivery')
            ->where('tenant_id', $tenantId)
            ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR);
        if ($invitationId !== null) {
            $query->whereKey($invitationId);
        }
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $invitation = $query->orderByDesc('id')->first();
        if (! $invitation instanceof AuthRegistrationInvitationModel) {
            return null;
        }

        $delivery = $invitation->latestDelivery;

        return [
            'id' => (int) $invitation->getKey(),
            'public_id' => (string) $invitation->getAttribute('public_id'),
            'email' => (string) $invitation->getAttribute('email'),
            'organization_unit_id' => (int) $invitation->getAttribute('organization_unit_id'),
            'role_id' => (int) $invitation->getAttribute('role_id'),
            'status' => (string) $invitation->getAttribute('status'),
            'expires_at' => $invitation->getAttribute('expires_at')?->toAtomString(),
            'accepted_at' => $invitation->getAttribute('accepted_at')?->toAtomString(),
            'accepted_by_user_id' => $invitation->getAttribute('accepted_by_user_id'),
            'revoked_at' => $invitation->getAttribute('revoked_at')?->toAtomString(),
            'revocation_reason' => $invitation->getAttribute('revocation_reason'),
            'row_version' => (int) $invitation->getAttribute('row_version'),
            'delivery' => $delivery instanceof AuthRegistrationInvitationDeliveryModel
                ? $this->deliveryStatus($delivery)
                : null,
        ];
    }

    /** @return array<string, mixed> */
    public function resendInitialAdministrator(int $tenantId, int $invitationId, int $expectedVersion): array
    {
        $delivery = DB::transaction(function () use ($tenantId, $invitationId, $expectedVersion): AuthRegistrationInvitationDeliveryModel {
            $invitation = $this->invitations->newQuery()
                ->whereKey($invitationId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $invitation->getAttribute('row_version') !== $expectedVersion
                || $invitation->getAttribute('status') !== RegistrationInvitationStatus::PENDING
                || $invitation->getAttribute('expires_at')->toImmutable() <= $this->clock->now()
            ) {
                throw new RuntimeException('The invitation changed, expired, or is no longer pending.');
            }

            if (trim((string) $invitation->getAttribute('delivery_token')) === '') {
                throw new RuntimeException('The invitation delivery token is no longer available. Replace the invitation instead.');
            }

            $latestAttempt = (int) $this->deliveries->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('invitation_id', $invitationId)
                ->lockForUpdate()
                ->max('attempt_number');

            $invitation->forceFill(['row_version' => $expectedVersion + 1])->save();

            return $this->createDelivery($invitation, $latestAttempt + 1);
        }, 3);

        $this->dispatchDelivery($tenantId, (int) $delivery->getKey());

        return $this->initialAdministratorStatus($tenantId, $invitationId) ?? [];
    }

    public function revokeInitialAdministrator(
        int $tenantId,
        int $invitationId,
        int $expectedVersion,
        string $reason,
    ): void {
        DB::transaction(function () use ($tenantId, $invitationId, $expectedVersion, $reason): void {
            $invitation = $this->invitations->newQuery()
                ->whereKey($invitationId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if (
                ! $invitation instanceof AuthRegistrationInvitationModel
                || (int) $invitation->getAttribute('row_version') !== $expectedVersion
                || $invitation->getAttribute('status') !== RegistrationInvitationStatus::PENDING
            ) {
                throw new RuntimeException('The invitation changed or is no longer pending.');
            }

            $this->revokeLocked($invitation, trim($reason));
        }, 3);
    }

    /** @return array{invitation_id:int,invitation_expires_at:string,delivery_status:string} */
    public function replaceInitialAdministrator(
        int $tenantId,
        int $invitationId,
        int $expectedVersion,
        int $organizationUnitId,
        int $roleId,
        string $email,
        string $reason,
    ): array {
        $email = strtolower(trim($email));
        $reason = trim($reason);
        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = $this->invitationExpiry();

        [$replacement, $delivery] = DB::transaction(function () use (
            $tenantId,
            $invitationId,
            $expectedVersion,
            $organizationUnitId,
            $roleId,
            $email,
            $reason,
            $plainToken,
            $expiresAt,
        ): array {
            $current = $this->invitations->newQuery()
                ->whereKey($invitationId)
                ->where('tenant_id', $tenantId)
                ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $current->getAttribute('row_version') !== $expectedVersion
                || $current->getAttribute('status') !== RegistrationInvitationStatus::PENDING
            ) {
                throw new RuntimeException('The invitation changed or is no longer pending.');
            }

            $this->revokeLocked($current, $reason);

            $replacement = $this->invitations->newQuery()->create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'role_id' => $roleId,
                'email' => $email,
                'token_hash' => $this->tokens->digestArbitrary($plainToken, 'registration-invitation'),
                'delivery_token' => $plainToken,
                'purpose' => RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR,
                'status' => RegistrationInvitationStatus::PENDING,
                'expires_at' => $expiresAt,
                'row_version' => 1,
            ]);
            $delivery = $this->createDelivery($replacement, 1);

            return [$replacement, $delivery];
        }, 3);

        $this->dispatchDelivery($tenantId, (int) $delivery->getKey());

        return [
            'invitation_id' => (int) $replacement->getKey(),
            'invitation_expires_at' => $expiresAt->format(DATE_ATOM),
            'delivery_status' => InvitationDeliveryStatus::QUEUED,
        ];
    }

    public function expirePending(): int
    {
        return $this->executionContext->runAsControlPlane(function (): int {
            $ids = $this->invitations->newQuery()
                ->where('status', RegistrationInvitationStatus::PENDING)
                ->where('expires_at', '<=', $this->clock->now())
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            if ($ids === []) {
                return 0;
            }

            $updated = $this->invitations->newQuery()
                ->whereIn('id', $ids)
                ->where('status', RegistrationInvitationStatus::PENDING)
                ->increment('row_version', 1, [
                    'status' => RegistrationInvitationStatus::EXPIRED,
                    'delivery_token' => null,
                    'updated_at' => $this->clock->now(),
                ]);

            $this->deliveries->newQuery()
                ->whereIn('invitation_id', $ids)
                ->whereIn('status', [
                    InvitationDeliveryStatus::QUEUED,
                    InvitationDeliveryStatus::SENDING,
                    InvitationDeliveryStatus::FAILED,
                ])
                ->increment('row_version', 1, [
                    'status' => InvitationDeliveryStatus::CANCELLED,
                    'cancelled_at' => $this->clock->now(),
                    'claim_token' => null,
                    'claimed_at' => null,
                    'lease_expires_at' => null,
                    'error_code' => 'AUTH_INVITATION_EXPIRED',
                    'error_message' => 'The invitation expired before delivery completed.',
                    'updated_at' => $this->clock->now(),
                ]);

            return $updated;
        });
    }

    private function invitationExpiry(): \DateTimeImmutable
    {
        return $this->clock->now()->add(new DateInterval(sprintf(
            'PT%dH',
            max(1, (int) config('module-auth.registration.invitation_expiry_hours', self::DEFAULT_EXPIRY_HOURS)),
        )));
    }

    private function createDelivery(
        AuthRegistrationInvitationModel $invitation,
        int $attemptNumber,
    ): AuthRegistrationInvitationDeliveryModel {
        return $this->deliveries->newQuery()->create([
            'public_id' => (string) Str::uuid(),
            'tenant_id' => (int) $invitation->getAttribute('tenant_id'),
            'invitation_id' => (int) $invitation->getKey(),
            'attempt_number' => $attemptNumber,
            'status' => InvitationDeliveryStatus::QUEUED,
            'processing_attempt_count' => 0,
            'requested_at' => $this->clock->now(),
        ]);
    }

    private function dispatchDelivery(int $tenantId, int $deliveryId): void
    {
        DeliverRegistrationInvitation::dispatch($tenantId, $deliveryId)->afterCommit();
    }

    private function revokeLocked(AuthRegistrationInvitationModel $invitation, string $reason): void
    {
        $tenantId = (int) $invitation->getAttribute('tenant_id');
        $invitationId = (int) $invitation->getKey();
        $invitation->forceFill([
            'status' => RegistrationInvitationStatus::REVOKED,
            'delivery_token' => null,
            'revoked_at' => $this->clock->now(),
            'revocation_reason' => $reason,
            'row_version' => (int) $invitation->getAttribute('row_version') + 1,
        ])->save();

        $this->cancelOpenDeliveries($tenantId, $invitationId, self::REVOKED_DELIVERY_REASON);
    }

    private function cancelOpenDeliveries(int $tenantId, int $invitationId, string $message): void
    {
        $this->deliveries->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('invitation_id', $invitationId)
            ->whereIn('status', [
                InvitationDeliveryStatus::QUEUED,
                InvitationDeliveryStatus::SENDING,
                InvitationDeliveryStatus::FAILED,
            ])
            ->increment('row_version', 1, [
                'status' => InvitationDeliveryStatus::CANCELLED,
                'cancelled_at' => $this->clock->now(),
                'claim_token' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
                'error_code' => 'AUTH_INVITATION_CANCELLED',
                'error_message' => $message,
                'updated_at' => $this->clock->now(),
            ]);
    }

    /** @return array<string, mixed> */
    private function deliveryStatus(AuthRegistrationInvitationDeliveryModel $delivery): array
    {
        return [
            'id' => (int) $delivery->getKey(),
            'public_id' => (string) $delivery->getAttribute('public_id'),
            'attempt_number' => (int) $delivery->getAttribute('attempt_number'),
            'status' => (string) $delivery->getAttribute('status'),
            'processing_attempt_count' => (int) $delivery->getAttribute('processing_attempt_count'),
            'requested_at' => $delivery->getAttribute('requested_at')?->toAtomString(),
            'sent_at' => $delivery->getAttribute('sent_at')?->toAtomString(),
            'delivered_at' => $delivery->getAttribute('delivered_at')?->toAtomString(),
            'bounced_at' => $delivery->getAttribute('bounced_at')?->toAtomString(),
            'failed_at' => $delivery->getAttribute('failed_at')?->toAtomString(),
            'cancelled_at' => $delivery->getAttribute('cancelled_at')?->toAtomString(),
            'provider' => $delivery->getAttribute('provider'),
            'provider_message_id' => $delivery->getAttribute('provider_message_id'),
            'error_code' => $delivery->getAttribute('error_code'),
            'error_message' => $delivery->getAttribute('error_message'),
        ];
    }
}
