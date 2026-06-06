<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

use Modules\Configuration\DTOs\ConfigurationValueData;
use Modules\Core\DTOs\DataRecord;

interface ConfigurationRecordMapperInterface
{
    public function toValueData(DataRecord $record): ConfigurationValueData;

    public function extractId(DataRecord $record): int|string;
}
