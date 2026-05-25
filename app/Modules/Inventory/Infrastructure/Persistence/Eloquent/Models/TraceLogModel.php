<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class TraceLogModel extends CoreModel
{


    protected $table = 'trace_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'identifier_id' => 'integer',
            'source_warehouse_id' => 'integer',
            'destination_warehouse_id' => 'integer',
            'source_location_id' => 'integer',
            'destination_location_id' => 'integer',
            'quantity' => 'decimal:4',
            'performed_by' => 'integer',
            'performed_at' => 'datetime',
            'reference_id' => 'integer',
            'entity_id' => 'integer',
        ]);
    }
}