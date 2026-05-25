<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class WriteOffModel extends CoreModel
{


    protected $table = 'write_offs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'document_id' => 'integer',
            'amount' => 'decimal:4',
            'journal_entry_id' => 'integer',
            'created_by' => 'integer'
        ]);
    }
}