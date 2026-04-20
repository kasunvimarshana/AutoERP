<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Support\Carbon;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $key
 * @property array<string, mixed>|null $value
 * @property string $group
 * @property bool $is_public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TenantSettingModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'tenant_settings';

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'group',
        'is_public',
    ];

    protected $casts = [
        'value' => 'array',
        'is_public' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }
}
