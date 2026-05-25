<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class ReceiptInspectionModel extends CoreModel
{


    protected $table = 'receipt_inspections';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'document_id' => 'integer',
            'inspected_by' => 'integer',
            'inspected_at' => 'datetime',
        ]);
    }
}