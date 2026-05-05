<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class NotificationModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fleet_notifications';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'org_unit_id',
        'row_version',
        'notification_number',
        'notification_type',
        'entity_type',
        'entity_id',
        'recipient_type',
        'recipient_id',
        'title',
        'message',
        'channel',
        'status',
        'sent_at',
        'read_at',
        'failed_reason',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'sent_at'    => 'datetime',
        'read_at'    => 'datetime',
        'metadata'   => 'array',
        'is_active'  => 'boolean',
        'row_version' => 'integer',
    ];
}
