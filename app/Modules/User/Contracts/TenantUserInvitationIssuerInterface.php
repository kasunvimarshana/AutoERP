<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface TenantUserInvitationIssuerInterface
{
    /** @return array{invitation_id:int,expires_at:string,delivery_status:string} */
    public function issueForUser(int $tenantId, int $userId, string $email): array;

    /** @return array{invitation_id:int,expires_at:string,delivery_status:string} */
    public function resendForUser(int $tenantId, int $userId): array;
}
