<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetadataEntity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'label',
        'label_plural',
        'table_name',
        'icon',
        'module',
        'description',
        'is_system',
        'is_active',
        'has_workflow',
        'has_audit_trail',
        'is_tenant_scoped',
        'ui_config',
        'api_config',
        'permissions',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'has_workflow' => 'boolean',
        'has_audit_trail' => 'boolean',
        'is_tenant_scoped' => 'boolean',
        'ui_config' => 'array',
        'api_config' => 'array',
        'permissions' => 'array',
    ];

    /**
     * Get the tenant that owns the entity.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the fields for the entity.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(MetadataField::class, 'entity_id');
    }

    /**
     * Get the workflows for the entity.
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(MetadataWorkflow::class, 'entity_id');
    }

    /**
     * Get list view fields.
     */
    public function listFields(): HasMany
    {
        return $this->fields()
            ->where('is_visible_list', true)
            ->orderBy('order');
    }

    /**
     * Get form fields.
     */
    public function formFields(): HasMany
    {
        return $this->fields()
            ->where('is_visible_form', true)
            ->orderBy('order');
    }

    /**
     * Get searchable fields.
     */
    public function searchableFields(): HasMany
    {
        return $this->fields()
            ->where('is_searchable', true);
    }

    /**
     * Scope to active entities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by module.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
