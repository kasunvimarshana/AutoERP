<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','recipient_id','recipient_email','recipient_phone',
        'type','channel','status','subject','content','metadata',
        'sent_at','failed_at','retry_count','saga_id','order_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at'  => 'datetime',
        'failed_at'=> 'datetime',
    ];

    const TYPES    = ['order_confirmed','order_cancelled','order_failed','payment_received','stock_alert'];
    const CHANNELS = ['email','sms','push'];
    const STATUSES = ['pending','sent','failed'];

    public function scopeByTenant($q, $tenantId){ return $q->where('tenant_id',$tenantId); }
    public function scopeByType($q, $type)      { return $q->where('type',$type); }
    public function scopeByChannel($q, $channel){ return $q->where('channel',$channel); }
    public function scopeFailed($q)             { return $q->where('status','failed'); }
}
