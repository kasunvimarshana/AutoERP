<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Contracts;

interface SlugGeneratorInterface
{
    public function generate(?string $preferredValue, ?string $sourceValue, ?string $fallback = null): string;
}
