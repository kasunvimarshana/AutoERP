<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PriceHistoryModel extends CoreModel
{
    protected $table = 'price_histories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'entity_id' => 'integer',
            'old_number' => 'decimal:4',
            'new_number' => 'decimal:4',
            'old_boolean' => 'boolean',
            'new_boolean' => 'boolean',
            'old_date' => 'date',
            'new_date' => 'date',
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
            'source_id' => 'integer',
        ]);
    }
}
