<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSettingModel extends Model
{
    use HasTenantScope;

    protected $table = 'tenant_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'group_id' => 'integer',
            'metadata' => 'array',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TenantSettingGroupModel::class, 'group_id');
    }
}

