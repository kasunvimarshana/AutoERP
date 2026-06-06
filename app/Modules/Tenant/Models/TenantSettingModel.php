<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

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
