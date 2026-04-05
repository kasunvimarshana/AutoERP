<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class NotificationPreferenceModel extends BaseModel
{
    protected $table = 'notification_preferences';

    protected $fillable = [
        'tenant_id', 'user_id', 'notification_type', 'channel', 'enabled',
    ];

    protected $casts = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'user_id'    => 'int',
        'enabled'    => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
