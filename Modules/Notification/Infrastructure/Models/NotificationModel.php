<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class NotificationModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'channel',
        'event_type',
        'template_id',
        'subject',
        'body',
        'status',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];
}
