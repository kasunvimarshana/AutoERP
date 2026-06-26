<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface PlatformOperatorCredentialProvisionerInterface
{
    /** @return array{minimum_length:int,mixed_case:bool,numbers:bool,symbols:bool} */
    public function passwordRequirements(): array;

    public function provision(int $platformOperatorId, string $plainPassword): void;

    public function revoke(int $platformOperatorId): void;
}
