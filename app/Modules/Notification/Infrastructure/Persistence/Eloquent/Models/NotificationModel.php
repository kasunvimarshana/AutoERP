<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class NotificationModel extends BaseModel
{
    protected $table = 'notifications_log';

    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'channel', 'title', 'body',
        'data', 'status', 'read_at', 'sent_at',
    ];

    protected $casts = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'user_id'    => 'int',
        'data'       => 'array',
        'read_at'    => 'datetime',
        'sent_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
