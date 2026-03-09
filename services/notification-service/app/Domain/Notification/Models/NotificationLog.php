<?php
declare(strict_types=1);
namespace App\Domain\Notification\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class NotificationLog extends Model {
    use HasUuids;
    protected $table = 'notification_logs';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    protected $fillable = ['tenant_id','channel','recipient','event','template','payload','status','error_message','saga_id','sent_at'];
    protected $casts = ['payload' => 'array', 'sent_at' => 'datetime'];
}
