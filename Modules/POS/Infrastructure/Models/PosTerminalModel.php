<?php

namespace Modules\POS\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PosTerminalModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'pos_terminals';

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'location_id',
        'is_active',
        'opening_balance',
    ];

    protected $casts = [
        'opening_balance' => 'string',
        'is_active'       => 'boolean',
    ];
}
