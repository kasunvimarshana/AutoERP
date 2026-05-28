<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentDefinitionModel extends Model
{
    protected $table = 'document_definitions';

    protected $fillable = [
        'tenant_id',
        'document_type_id',
        'version',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
