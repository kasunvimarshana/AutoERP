<?php

declare(strict_types=1);

namespace Modules\Configuration\Exceptions;

use Modules\Core\Exceptions\DomainException;

final class ConfigurationNotFoundException extends DomainException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('Configuration key "%s" was not found.', $key));
    }
}
