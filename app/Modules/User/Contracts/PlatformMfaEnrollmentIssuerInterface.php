<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface PlatformMfaEnrollmentIssuerInterface
{
    /** @return array{enrollment_proof:string,provisioning_uri:string}|null */
    public function issueForOperator(int $operatorId, string $email): ?array;

    public function revokeForOperator(int $operatorId): void;
}
