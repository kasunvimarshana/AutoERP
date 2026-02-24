<?php

namespace Modules\POS\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PosSessionModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'pos_sessions';

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'terminal_id',
        'cashier_id',
        'status',
        'opening_cash',
        'closing_cash',
        'total_sales',
        'order_count',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_cash' => 'string',
        'closing_cash' => 'string',
        'total_sales'  => 'string',
        'opened_at'    => 'datetime',
        'closed_at'    => 'datetime',
    ];
}
