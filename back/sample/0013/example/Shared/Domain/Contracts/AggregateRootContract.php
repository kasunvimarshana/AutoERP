<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

use App\Shared\Domain\ValueObjects\Uuid;

interface AggregateRootContract
{
    public function id(): Uuid;

    /** @return array<object> */
    public function releaseEvents(): array;
}
