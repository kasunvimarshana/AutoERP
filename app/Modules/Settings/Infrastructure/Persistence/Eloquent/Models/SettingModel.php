<?php

declare(strict_types=1);

namespace Modules\Settings\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class SettingModel extends BaseModel
{
    protected $table = 'settings';

    protected $fillable = [
        'tenant_id',
        'group_key',
        'setting_key',
        'setting_type',
        'value',
        'default_value',
        'label',
        'description',
        'is_system',
        'is_editable',
        'metadata',
    ];

    protected $casts = [
        'tenant_id'  => 'integer',
        'is_system'  => 'boolean',
        'is_editable' => 'boolean',
        'metadata'   => 'array',
    ];
}
