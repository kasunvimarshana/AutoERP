<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface UuidGeneratorInterface
{
    public function generate(): string;
}
