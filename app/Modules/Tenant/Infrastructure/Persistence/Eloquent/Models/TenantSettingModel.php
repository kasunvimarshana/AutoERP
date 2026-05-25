<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantSettingGroupModel;

final class TenantSettingModel extends CoreModel
{
    protected $table = 'tenant_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'metadata' => 'array',
            'tenant_id' => 'integer',
            'group_id' => 'integer',
        ]);
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
