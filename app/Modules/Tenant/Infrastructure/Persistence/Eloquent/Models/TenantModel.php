<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class TenantModel extends BaseModel
{
    use HasUuid;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'domain',
        'slug',
        'status',
        'plan',
        'settings',
        'metadata',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'id'       => 'string',
            'settings' => 'array',
            'metadata' => 'array',
        ]);
    }
}
