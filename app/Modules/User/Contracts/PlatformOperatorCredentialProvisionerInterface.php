<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface PlatformOperatorCredentialProvisionerInterface
{
    public function provision(int $platformOperatorId, string $plainPassword): void;

    public function revoke(int $platformOperatorId): void;
}
