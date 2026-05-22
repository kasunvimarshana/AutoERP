<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\DTOs;

use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

final readonly class DeleteConfigurationRecordDTO
{
    public function __construct(
        public ConfigurationRecordType $type,
        public int $id,
    ) {
    }
}
