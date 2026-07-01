<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Modules\Core\Contracts\ClockInterface;

final class SystemClock implements ClockInterface
{
    private readonly DateTimeZone $utc;

    public function __construct()
    {
        $this->utc = new DateTimeZone('UTC');
    }

    public function now(): DateTimeImmutable
    {
        return CarbonImmutable::now($this->utc);
    }
}
