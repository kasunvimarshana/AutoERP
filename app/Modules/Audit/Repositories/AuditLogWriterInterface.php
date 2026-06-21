<?php

declare(strict_types=1);

namespace Modules\Audit\Repositories;

use Modules\Core\DTOs\DataRecord;

interface AuditLogWriterInterface
{
    /** @param array<string, mixed> $attributes */
    public function append(array $attributes): DataRecord;
}
