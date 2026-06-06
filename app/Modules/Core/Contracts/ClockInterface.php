<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
