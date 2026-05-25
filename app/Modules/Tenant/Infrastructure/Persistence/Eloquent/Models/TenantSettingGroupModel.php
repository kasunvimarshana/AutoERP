<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantSettingGroupModel extends Model
{
    use HasTenantScope;

    protected $table = 'tenant_setting_groups';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'parent_id' => 'integer',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TenantSettingGroupModel::class, 'parent_id');
    }

    public function tenantSettingGroupsAsParent(): HasMany
    {
        return $this->hasMany(TenantSettingGroupModel::class, 'parent_id');
    }

    public function tenantSettings(): HasMany
    {
        return $this->hasMany(TenantSettingModel::class, 'group_id');
    }
}

