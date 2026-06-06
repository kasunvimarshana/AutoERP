<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface SlugGeneratorInterface
{
    public function generate(?string $preferredValue, ?string $sourceValue, ?string $fallback = null): string;
}
