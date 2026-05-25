<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Support;

use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\UuidGeneratorInterface;

final class LaravelUuidGenerator implements UuidGeneratorInterface
{
    public function generate(): string
    {
        return (string) Str::uuid();
    }
}
