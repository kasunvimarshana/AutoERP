<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

use App\Shared\Domain\ValueObjects\Uuid;

interface EntityContract
{
    public function id(): Uuid;
    public function equals(self $other): bool;
}
