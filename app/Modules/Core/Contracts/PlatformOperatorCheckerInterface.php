<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface PlatformOperatorCheckerInterface
{
    public function isPlatformOperator(int $userId): bool;
}
