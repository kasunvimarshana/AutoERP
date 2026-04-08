<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class SettingModel extends BaseModel
{
    use HasAudit, HasTenant, HasUuid;

    protected $table = 'settings';

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'group',
        'type',
        'is_public',
        'description',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'id'        => 'string',
            'tenant_id' => 'string',
            'is_public' => 'boolean',
        ]);
    }
}
