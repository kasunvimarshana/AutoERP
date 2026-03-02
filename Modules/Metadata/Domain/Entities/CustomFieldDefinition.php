<?php

declare(strict_types=1);

namespace Modules\Metadata\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * CustomFieldDefinition entity.
 *
 * Defines a custom field for a given entity type within a tenant.
 * Field types: text, number, date, boolean, select, multiselect, textarea.
 */
class CustomFieldDefinition extends Model
{
    use HasTenant;

    protected $table = 'custom_field_definitions';

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'field_name',
        'field_label',
        'field_type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
        'validation_rules',
    ];

    protected $casts = [
        'options'          => 'array',
        'validation_rules' => 'array',
        'is_required'      => 'boolean',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'field_definition_id');
    }
}
