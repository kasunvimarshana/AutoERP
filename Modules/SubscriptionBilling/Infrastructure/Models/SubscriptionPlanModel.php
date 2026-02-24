<?php

namespace Modules\SubscriptionBilling\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class SubscriptionPlanModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'subscription_plans';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'code',
        'description',
        'billing_cycle',
        'price',
        'trial_days',
        'is_active',
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
