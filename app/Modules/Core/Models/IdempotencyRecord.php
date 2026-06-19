<?php

declare(strict_types=1);

namespace Modules\Core\Models;

final class IdempotencyRecord extends CoreModel
{
    protected $table = 'idempotency_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'result' => 'array',
            'document_ids' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
