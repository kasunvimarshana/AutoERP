<?php

namespace Modules\Integration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class WebhookModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'integration_webhooks';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'url',
        'events',
        'signing_secret',
        'is_active',
    ];

    protected $casts = [
        'events'    => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
