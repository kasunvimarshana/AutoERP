<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantAuthenticationProvisionerInterface
{
    /** @return array{provider_id:int} */
    public function provisionProvider(int $tenantId): array;

    /** @return array{invitation_id:int,invitation_expires_at:string,delivery_status:string} */
    public function issueInitialAdministratorInvitation(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $email,
    ): array;

    public function providerIsReady(int $tenantId, bool $lockForUpdate = false): bool;

    public function acceptedInitialAdministratorUserId(int $tenantId, ?int $invitationId = null, bool $lockForUpdate = false): ?int;

    public function hasPendingInitialAdministratorInvitation(int $tenantId, ?int $invitationId = null): bool;

    /** @return array<string, mixed>|null */
    public function initialAdministratorInvitationStatus(int $tenantId, ?int $invitationId = null): ?array;

    /** @return array<string, mixed> */
    public function resendInitialAdministratorInvitation(int $tenantId, int $invitationId, int $expectedVersion): array;

    public function revokeInitialAdministratorInvitation(
        int $tenantId,
        int $invitationId,
        int $expectedVersion,
        string $reason,
    ): void;

    /** @return array{invitation_id:int,invitation_expires_at:string,delivery_status:string} */
    public function replaceInitialAdministratorInvitation(
        int $tenantId,
        int $invitationId,
        int $expectedVersion,
        int $organizationUnitId,
        int $roleId,
        string $email,
        string $reason,
    ): array;
}
