<?php
declare(strict_types=1);
namespace App\Domain\Notification\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class WebhookEndpoint extends Model {
    use HasUuids, SoftDeletes;
    protected $table = 'webhook_endpoints';
    protected $fillable = ['tenant_id','name','url','secret','events','is_active','headers'];
    protected $casts = ['events' => 'array', 'headers' => 'array', 'is_active' => 'boolean', 'deleted_at' => 'datetime'];
    protected $hidden = ['secret'];
}
