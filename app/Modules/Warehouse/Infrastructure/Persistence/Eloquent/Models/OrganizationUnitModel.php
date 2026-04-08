<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class OrganizationUnitModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'organization_units';

    protected $fillable = [
        'tenant_id', 'parent_id', 'code', 'name', 'type',
        'description', 'manager_user_id', 'is_active', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'sort_order'      => 'integer',
        'manager_user_id' => 'integer',
        'metadata'        => 'array',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
