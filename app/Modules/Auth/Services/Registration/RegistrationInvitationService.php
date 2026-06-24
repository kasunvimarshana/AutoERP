<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\AuthRegistrationInvitationModel;
use Modules\Core\DTOs\DataRecord;
use RuntimeException;

final class RegistrationInvitationService
{
    private const INITIAL_ADMIN_PURPOSE = 'initial_administrator';
    private const DEFAULT_EXPIRY_HOURS = 72;

    public function __construct(
        private readonly AuthRegistrationInvitationModel $invitations,
    ) {}

    /** @return array{invitation_id:int,invitation_token:string,invitation_expires_at:string} */
    public function issueInitialAdministrator(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $email,
    ): array {
        $email = strtolower(trim($email));
        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = new DateTimeImmutable(sprintf(
            '+%d hours',
            max(1, (int) config('module-auth.registration.invitation_expiry_hours', self::DEFAULT_EXPIRY_HOURS)),
        ));

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
                ->where('purpose', self::INITIAL_ADMIN_PURPOSE)
                ->where('status', 'pending')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_at' => now(),
                ]);

            return $this->invitations->newQuery()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'role_id' => $roleId,
                'email' => $email,
                'token_hash' => hash('sha256', $plainToken),
                'purpose' => self::INITIAL_ADMIN_PURPOSE,
                'status' => 'pending',
                'expires_at' => $expiresAt,
                'metadata' => ['issued_by' => 'tenant_onboarding'],
                'row_version' => 1,
            ]);
        }, 3);

        return [
            'invitation_id' => (int) $invitation->getKey(),
            'invitation_token' => $plainToken,
            'invitation_expires_at' => $expiresAt->format(DATE_ATOM),
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
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->lockForUpdate()
            ->first();

        return $invitation instanceof AuthRegistrationInvitationModel
            ? new DataRecord($invitation->attributesToArray())
            : null;
    }

    public function accept(int $tenantId, int $invitationId, int $userId, int $expectedVersion): void
    {
        $updated = $this->invitations->newQuery()
            ->whereKey($invitationId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('row_version', $expectedVersion)
            ->where('expires_at', '>', now())
            ->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'accepted_by_user_id' => $userId,
                'row_version' => $expectedVersion + 1,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Registration invitation changed or expired before it could be accepted.');
        }
    }

    public function hasUsableInitialAdministratorInvitation(int $tenantId): bool
    {
        return $this->invitations->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('purpose', self::INITIAL_ADMIN_PURPOSE)
            ->where(function ($query): void {
                $query->where('status', 'accepted')
                    ->orWhere(function ($pending): void {
                        $pending->where('status', 'pending')
                            ->where('expires_at', '>', now());
                    });
            })
            ->exists();
    }
}
