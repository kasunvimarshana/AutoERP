<?php

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class SystemSettingModel extends BaseModel
{
    protected $table = 'system_settings';

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_public'    => 'boolean',
    ];
}
