<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

interface UuidGeneratorInterface
{
    public function generate(): string;
}
