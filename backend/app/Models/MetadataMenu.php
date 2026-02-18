<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetadataMenu extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'label',
        'icon',
        'route',
        'url',
        'entity_name',
        'permission',
        'order',
        'is_active',
        'is_system',
        'config',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'config' => 'array',
    ];

    /**
     * Get the tenant that owns the menu.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the parent menu item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MetadataMenu::class, 'parent_id');
    }

    /**
     * Get the child menu items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MetadataMenu::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope to active menus.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to root menus.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id')->orderBy('order');
    }

    /**
     * Check if user has permission to access this menu.
     */
    public function userHasAccess($user): bool
    {
        if (! $this->permission) {
            return true;
        }

        return $user->hasPermission($this->permission);
    }
}
