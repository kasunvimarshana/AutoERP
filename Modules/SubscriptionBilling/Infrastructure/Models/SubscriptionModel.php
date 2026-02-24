<?php

namespace Modules\SubscriptionBilling\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class SubscriptionModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'subscriptions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'plan_id',
        'subscriber_type',
        'subscriber_id',
        'status',
        'amount',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end'   => 'datetime',
        'trial_ends_at'        => 'datetime',
        'cancelled_at'         => 'datetime',
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
