<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

interface SlugGeneratorInterface
{
    public function generate(?string $preferredValue, ?string $sourceValue, string $fallback = 'n-a'): string;
}
