<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Audit\Repositories;

use Modules\Core\DTOs\DataRecord;

interface AuditLogWriterInterface
{
    /** @param array<string, mixed> $attributes */
    public function append(array $attributes): DataRecord;
}
