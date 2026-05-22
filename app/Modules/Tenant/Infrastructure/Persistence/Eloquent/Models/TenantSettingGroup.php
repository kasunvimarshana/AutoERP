<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Concerns\HasTenant;

class TenantSettingGroup extends Model
{
    use HasTenant;

    protected $table = 'tenant_setting_groups';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant',
            'tenant_id'
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\TenantSettingGroup',
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\TenantSettingGroup',
            'parent_id'
        );
    }

    public function settings(): HasMany
    {
        return $this->hasMany(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\TenantSetting',
            'group_id'
        );
    }
}
