<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use DateInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Constants\InvitationDeliveryStatus;
use Modules\Auth\Constants\RegistrationInvitationPurpose;
use Modules\Auth\Constants\RegistrationInvitationStatus;
use Modules\Auth\Jobs\DeliverInitialAdministratorInvitation;
use Modules\Auth\Models\AuthRegistrationInvitationModel;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\DataRecord;
use RuntimeException;

final class RegistrationInvitationService
{
    private const DEFAULT_EXPIRY_HOURS = 72;
    private const REPLACED_REASON = 'Replaced by a newer initial administrator invitation.';

    public function __construct(
        private readonly AuthRegistrationInvitationModel $invitations,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly ClockInterface $clock,
    ) {}

    /** @return array{invitation_id:int,invitation_expires_at:string,delivery_status:string} */
    public function issueInitialAdministrator(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $email,
    ): array {
        $email = strtolower(trim($email));
        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = $this->clock->now()->add(new DateInterval(sprintf(
            'PT%dH',
            max(1, (int) config('module-auth.registration.invitation_expiry_hours', self::DEFAULT_EXPIRY_HOURS)),
        )));

        $invitation = DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $roleId,
            $email,
            $plainToken,
            $expiresAt,
        ): AuthRegistrationInvitationModel {
            $this->invitations->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
                ->where('status', RegistrationInvitationStatus::PENDING)
                ->update([
                    'status' => RegistrationInvitationStatus::REVOKED,
                    'delivery_token' => null,
                    'revoked_at' => $this->clock->now(),
                    'revocation_reason' => self::REPLACED_REASON,
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_at' => $this->clock->now(),
                ]);

            return $this->invitations->newQuery()->create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'role_id' => $roleId,
                'email' => $email,
                'token_hash' => hash('sha256', $plainToken),
                'delivery_token' => $plainToken,
                'purpose' => RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR,
                'status' => RegistrationInvitationStatus::PENDING,
                'delivery_status' => InvitationDeliveryStatus::PENDING,
                'delivery_requested_at' => $this->clock->now(),
                'expires_at' => $expiresAt,
                'row_version' => 1,
            ]);
        }, 3);

        DeliverInitialAdministratorInvitation::dispatch($tenantId, (int) $invitation->getKey())->afterCommit();

        return [
            'invitation_id' => (int) $invitation->getKey(),
            'invitation_expires_at' => $expiresAt->format(DATE_ATOM),
            'delivery_status' => InvitationDeliveryStatus::PENDING,
        ];
    }

    public function findValid(int $tenantId, string $email, ?string $plainToken): ?DataRecord
    {
        $plainToken = trim((string) $plainToken);
        if ($plainToken === '') {
            return null;
        }

        $invitation = $this->invitations->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('email', strtolower(trim($email)))
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('status', RegistrationInvitationStatus::PENDING)
            ->where('expires_at', '>', $this->clock->now())
            ->lockForUpdate()
            ->first();

        return $invitation instanceof AuthRegistrationInvitationModel
            ? new DataRecord($invitation->attributesToArray())
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
                ->with('tenant:id,name')
                ->where('token_hash', hash('sha256', $plainToken))
                ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
                ->where('status', RegistrationInvitationStatus::PENDING)
                ->where('expires_at', '>', $this->clock->now())
                ->first();

            if (! $invitation instanceof AuthRegistrationInvitationModel) {
                return null;
            }

            return [
                'tenant_id' => (int) $invitation->getAttribute('tenant_id'),
                'email' => (string) $invitation->getAttribute('email'),
                'tenant_name' => (string) ($invitation->tenant?->getAttribute('name') ?? 'Tenant'),
                'expires_at' => $invitation->getAttribute('expires_at')->toAtomString(),
            ];
        });
    }

    public function accept(int $tenantId, int $invitationId, int $userId, int $expectedVersion): void
    {
        $updated = $this->invitations->newQuery()
            ->whereKey($invitationId)
            ->where('tenant_id', $tenantId)
            ->where('status', RegistrationInvitationStatus::PENDING)
            ->where('row_version', $expectedVersion)
            ->where('expires_at', '>', $this->clock->now())
            ->update([
                'status' => RegistrationInvitationStatus::ACCEPTED,
                'delivery_token' => null,
                'accepted_at' => $this->clock->now(),
                'accepted_by_user_id' => $userId,
                'row_version' => $expectedVersion + 1,
                'updated_at' => $this->clock->now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Registration invitation changed or expired before it could be accepted.');
        }
    }

    public function acceptedInitialAdministratorUserId(
        int $tenantId,
        ?int $invitationId = null,
        bool $lockForUpdate = false,
    ): ?int
    {
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

    public function hasPendingInitialAdministratorInvitation(int $tenantId, ?int $invitationId = null): bool
    {
        $query = $this->invitations->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
            ->where('status', RegistrationInvitationStatus::PENDING)
            ->where('expires_at', '>', $this->clock->now());
        if ($invitationId !== null) {
            $query->whereKey($invitationId);
        }

        return $query->exists();
    }

    /** @return array<string, mixed>|null */
    public function initialAdministratorStatus(int $tenantId, ?int $invitationId = null): ?array
    {
        $query = $this->invitations->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR);
        if ($invitationId !== null) {
            $query->whereKey($invitationId);
        }

        $invitation = $query->orderByDesc('id')->first();
        if (! $invitation instanceof AuthRegistrationInvitationModel) {
            return null;
        }

        return $invitation->only([
            'id',
            'public_id',
            'email',
            'status',
            'delivery_status',
            'delivery_attempt_count',
            'delivery_requested_at',
            'delivered_at',
            'delivery_error_code',
            'delivery_error_message',
            'expires_at',
            'accepted_at',
            'accepted_by_user_id',
            'revoked_at',
            'revocation_reason',
            'row_version',
        ]);
    }

    /** @return array<string, mixed> */
    public function resendInitialAdministrator(int $tenantId, int $invitationId, int $expectedVersion): array
    {
        $invitation = DB::transaction(function () use ($tenantId, $invitationId, $expectedVersion): AuthRegistrationInvitationModel {
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

            $invitation->forceFill([
                'delivery_status' => InvitationDeliveryStatus::PENDING,
                'delivery_requested_at' => $this->clock->now(),
                'delivery_error_code' => null,
                'delivery_error_message' => null,
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $invitation;
        }, 3);

        DeliverInitialAdministratorInvitation::dispatch($tenantId, $invitationId)->afterCommit();

        return $this->initialAdministratorStatus($tenantId, $invitationId) ?? [];
    }

    public function revokeInitialAdministrator(
        int $tenantId,
        int $invitationId,
        int $expectedVersion,
        string $reason,
    ): void {
        $updated = $this->invitations->newQuery()
            ->whereKey($invitationId)
            ->where('tenant_id', $tenantId)
            ->where('status', RegistrationInvitationStatus::PENDING)
            ->where('row_version', $expectedVersion)
            ->update([
                'status' => RegistrationInvitationStatus::REVOKED,
                'delivery_token' => null,
                'revoked_at' => $this->clock->now(),
                'revocation_reason' => trim($reason),
                'row_version' => $expectedVersion + 1,
                'updated_at' => $this->clock->now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('The invitation changed or is no longer pending.');
        }
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
        $expiresAt = $this->clock->now()->add(new DateInterval(sprintf(
            'PT%dH',
            max(1, (int) config('module-auth.registration.invitation_expiry_hours', self::DEFAULT_EXPIRY_HOURS)),
        )));

        $replacement = DB::transaction(function () use (
            $tenantId,
            $invitationId,
            $expectedVersion,
            $organizationUnitId,
            $roleId,
            $email,
            $reason,
            $plainToken,
            $expiresAt,
        ): AuthRegistrationInvitationModel {
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

            $current->forceFill([
                'status' => RegistrationInvitationStatus::REVOKED,
                'delivery_token' => null,
                'revoked_at' => $this->clock->now(),
                'revocation_reason' => $reason,
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $this->invitations->newQuery()->create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'role_id' => $roleId,
                'email' => $email,
                'token_hash' => hash('sha256', $plainToken),
                'delivery_token' => $plainToken,
                'purpose' => RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR,
                'status' => RegistrationInvitationStatus::PENDING,
                'delivery_status' => InvitationDeliveryStatus::PENDING,
                'delivery_requested_at' => $this->clock->now(),
                'expires_at' => $expiresAt,
                'row_version' => 1,
            ]);
        }, 3);

        DeliverInitialAdministratorInvitation::dispatch($tenantId, (int) $replacement->getKey())->afterCommit();

        return [
            'invitation_id' => (int) $replacement->getKey(),
            'invitation_expires_at' => $expiresAt->format(DATE_ATOM),
            'delivery_status' => InvitationDeliveryStatus::PENDING,
        ];
    }

    public function expirePending(): int
    {
        return $this->executionContext->runAsControlPlane(fn (): int => $this->invitations->newQuery()
            ->where('status', RegistrationInvitationStatus::PENDING)
            ->where('expires_at', '<=', $this->clock->now())
            ->update([
                'status' => RegistrationInvitationStatus::EXPIRED,
                'delivery_token' => null,
                'row_version' => DB::raw('row_version + 1'),
                'updated_at' => $this->clock->now(),
            ]));
    }
}
