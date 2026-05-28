<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentItemModel extends Model
{
    protected $table = 'document_items';

    protected $fillable = [
        'tenant_id',
        'document_id',
        'item_type',
        'description',
        'line_total',
        'sequence',
    ];
}
