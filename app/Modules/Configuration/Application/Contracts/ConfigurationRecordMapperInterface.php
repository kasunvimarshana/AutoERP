<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts;

use Modules\Configuration\Application\DTOs\ConfigurationValueData;
use Modules\Core\Application\DTO\DataRecord;

interface ConfigurationRecordMapperInterface
{
    public function toValueData(DataRecord $record): ConfigurationValueData;

    public function extractId(DataRecord $record): int|string;
}
