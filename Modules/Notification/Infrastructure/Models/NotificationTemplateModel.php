<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class NotificationTemplateModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'notification_templates';

    protected $fillable = [
        'tenant_id',
        'channel',
        'event_type',
        'name',
        'subject',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
