<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface PlatformOperatorSessionRevokerInterface
{
    public function revokeAllForOperator(int $operatorId, string $reason): int;
}
