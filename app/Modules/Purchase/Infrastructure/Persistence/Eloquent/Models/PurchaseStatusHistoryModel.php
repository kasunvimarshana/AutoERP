<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PurchaseStatusHistoryModel extends CoreModel
{
    protected $table = 'purchase_status_histories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'entity_id' => 'integer',
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ]);
    }
}
