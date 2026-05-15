<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTypeModel extends Model
{
    protected $table = 'document_types';

    protected $fillable = [
        'name',
        'code',
        'default_status',
        'is_active',
        'requires_source',
    ];
}
