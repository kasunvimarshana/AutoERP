<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use DateTimeImmutable;
use Modules\Core\Contracts\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }
}
