<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Support;

use DateTimeImmutable;
use Modules\Core\Application\Contracts\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
