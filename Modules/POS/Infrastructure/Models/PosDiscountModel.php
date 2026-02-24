<?php

namespace Modules\POS\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PosDiscountModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope;

    protected $table = 'pos_discounts';

    protected $fillable = [
        'id',
        'tenant_id',
        'code',
        'name',
        'type',
        'value',
        'usage_limit',
        'times_used',
        'expires_at',
        'is_active',
        'description',
    ];

    protected $casts = [
        'value'       => 'string',
        'usage_limit' => 'integer',
        'times_used'  => 'integer',
        'expires_at'  => 'datetime',
        'is_active'   => 'boolean',
    ];
}
