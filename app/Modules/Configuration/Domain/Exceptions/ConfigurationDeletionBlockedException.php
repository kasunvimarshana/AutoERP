<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Exceptions;

use RuntimeException;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

final class ConfigurationDeletionBlockedException extends RuntimeException
{
    public static function dueToDependencies(ConfigurationRecordType $type, int $id, array $dependencies): self
    {
        $pairs = [];
        foreach ($dependencies as $table => $count) {
            $pairs[] = sprintf('%s:%d', $table, $count);
        }

        return new self(sprintf(
            'Cannot delete %s id=%d because dependent records exist (%s).',
            $type->value,
            $id,
            implode(', ', $pairs)
        ));
    }
}
