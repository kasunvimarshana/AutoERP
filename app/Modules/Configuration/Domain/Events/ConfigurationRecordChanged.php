<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Events;

use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

final readonly class ConfigurationRecordChanged
{
    public function __construct(
        public ConfigurationRecordType $type,
        public int $id,
        public string $action,
    ) {
    }
}
