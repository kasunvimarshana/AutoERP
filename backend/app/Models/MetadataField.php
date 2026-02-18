<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetadataField extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entity_id',
        'name',
        'label',
        'type',
        'column_name',
        'description',
        'is_required',
        'is_unique',
        'is_searchable',
        'is_filterable',
        'is_sortable',
        'is_visible_list',
        'is_visible_detail',
        'is_visible_form',
        'is_readonly',
        'is_system',
        'order',
        'default_value',
        'validation_rules',
        'options',
        'ui_config',
        'permissions',
        'relationship_entity',
        'relationship_type',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_searchable' => 'boolean',
        'is_filterable' => 'boolean',
        'is_sortable' => 'boolean',
        'is_visible_list' => 'boolean',
        'is_visible_detail' => 'boolean',
        'is_visible_form' => 'boolean',
        'is_readonly' => 'boolean',
        'is_system' => 'boolean',
        'order' => 'integer',
        'validation_rules' => 'array',
        'options' => 'array',
        'ui_config' => 'array',
        'permissions' => 'array',
    ];

    /**
     * Get the entity that owns the field.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(MetadataEntity::class, 'entity_id');
    }

    /**
     * Check if field is a relationship field.
     */
    public function isRelationship(): bool
    {
        return ! empty($this->relationship_entity) && ! empty($this->relationship_type);
    }

    /**
     * Get Laravel validation rules.
     */
    public function getValidationRulesAttribute($value): array
    {
        $rules = json_decode($value, true) ?? [];

        // Add standard rules based on field properties
        $standardRules = [];

        if ($this->is_required) {
            $standardRules[] = 'required';
        }

        if ($this->is_unique && $this->column_name) {
            $standardRules[] = 'unique:'.$this->entity->table_name.','.$this->column_name;
        }

        return array_merge($standardRules, $rules);
    }

    /**
     * Scope to visible fields.
     */
    public function scopeVisible($query, string $context = 'list')
    {
        $field = "is_visible_{$context}";

        return $query->where($field, true)->orderBy('order');
    }

    /**
     * Scope to searchable fields.
     */
    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }
}
