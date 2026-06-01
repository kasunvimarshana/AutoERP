<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PurchaseLedgerNoteModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'purchase_ledger_notes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'source_id' => 'integer',
            'is_visible_to_api' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }
}
