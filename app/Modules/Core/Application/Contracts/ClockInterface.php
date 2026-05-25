<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
