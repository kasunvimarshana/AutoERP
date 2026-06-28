<?php

declare(strict_types=1);

namespace Modules\Idempotency\Models;

use Modules\Core\Models\TenantOwnedModel;
use Modules\Idempotency\Enums\IdempotencyStatus;

final class IdempotencyRecord extends TenantOwnedModel
{
    protected $table = 'idempotency_records';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'status' => IdempotencyStatus::class,
            'result' => 'array',
            'document_ids' => 'array',
            'created_by' => 'integer',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
