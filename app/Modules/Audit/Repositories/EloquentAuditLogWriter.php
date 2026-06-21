<?php

declare(strict_types=1);

namespace Modules\Audit\Repositories;

use Modules\Audit\Models\AuditLog;
use Modules\Core\DTOs\DataRecord;

final class EloquentAuditLogWriter implements AuditLogWriterInterface
{
    public function __construct(private readonly AuditLog $model) {}

    public function append(array $attributes): DataRecord
    {
        $record = $this->model->newQuery()->create($attributes);

        return new DataRecord($record->attributesToArray());
    }
}
