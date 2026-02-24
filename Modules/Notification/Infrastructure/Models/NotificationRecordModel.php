<?php
namespace Modules\Notification\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class NotificationRecordModel extends Model
{
    use HasUuids, HasTenantScope;
    protected $table = 'notification_records';
    protected $fillable = [
        'id', 'tenant_id', 'user_id', 'type', 'channel', 'data', 'status', 'read_at',
    ];
    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];
}
