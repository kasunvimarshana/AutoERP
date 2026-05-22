<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Exceptions;

use RuntimeException;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

final class ConfigurationRecordNotFoundException extends RuntimeException
{
    public static function forId(ConfigurationRecordType $type, int $id): self
    {
        return new self(sprintf('Configuration record not found: type=%s id=%d', $type->value, $id));
    }
}
