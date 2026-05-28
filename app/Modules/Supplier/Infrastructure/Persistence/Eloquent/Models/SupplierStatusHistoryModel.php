<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierStatusHistoryModel extends CoreModel
{
    protected $table = 'supplier_status_histories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}
