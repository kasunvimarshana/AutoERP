<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Support\Str;
use Modules\Core\Contracts\UuidGeneratorInterface;

final class LaravelUuidGenerator implements UuidGeneratorInterface
{
    public function generate(): string
    {
        return (string) Str::uuid();
    }
}
