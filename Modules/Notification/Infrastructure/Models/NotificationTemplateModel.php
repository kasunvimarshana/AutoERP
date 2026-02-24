<?php
namespace Modules\Notification\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class NotificationTemplateModel extends Model
{
    use HasUuids, HasTenantScope;
    protected $table = 'notification_templates';
    protected $fillable = [
        'id', 'tenant_id', 'event_type', 'channel', 'subject', 'body', 'variables', 'is_active',
    ];
    protected $casts = ['variables' => 'array', 'is_active' => 'boolean'];
}
