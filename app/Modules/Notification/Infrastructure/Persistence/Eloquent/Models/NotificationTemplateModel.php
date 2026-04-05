<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class NotificationTemplateModel extends BaseModel
{
    protected $table = 'notification_templates';

    protected $fillable = [
        'tenant_id', 'type', 'name', 'channel', 'subject', 'body',
        'variables', 'is_active',
    ];

    protected $casts = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'variables'  => 'array',
        'is_active'  => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
