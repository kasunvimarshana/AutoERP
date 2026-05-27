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
        'header_schema',
        'allowed_item_types',
        'validation_rules',
        'form_layout',
        'is_active',
    ];

    protected $casts = [
        'header_schema' => 'array',
        'allowed_item_types' => 'array',
        'validation_rules' => 'array',
        'form_layout' => 'array',
        'is_active' => 'boolean',
    ];
}
