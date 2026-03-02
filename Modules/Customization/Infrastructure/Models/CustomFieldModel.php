<?php

declare(strict_types=1);

namespace Modules\Customization\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class CustomFieldModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'custom_fields';

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'field_key',
        'field_label',
        'field_type',
        'is_required',
        'default_value',
        'sort_order',
        'options',
        'validation_rules',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
        'sort_order' => 'integer',
    ];
}
