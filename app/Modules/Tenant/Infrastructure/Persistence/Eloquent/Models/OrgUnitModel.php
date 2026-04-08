<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class OrgUnitModel extends BaseModel
{
    use HasTenant, HasUuid;

    protected $table = 'org_units';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'parent_id',
        'name',
        'code',
        'type',
        'description',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgUnitModel::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrgUnitModel::class, 'parent_id');
    }
}
