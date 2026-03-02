<?php

declare(strict_types=1);

namespace Modules\Metadata\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * CustomFieldValue entity.
 *
 * Stores the value of a custom field for a specific entity instance.
 */
class CustomFieldValue extends Model
{
    use HasTenant;

    protected $table = 'custom_field_values';

    protected $fillable = [
        'tenant_id',
        'field_definition_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class, 'field_definition_id');
    }
}
