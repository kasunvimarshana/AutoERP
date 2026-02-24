<?php

namespace Modules\POS\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class LoyaltyCardModel extends Model
{
    use HasUuids, HasTenantScope;

    protected $table = 'pos_loyalty_cards';

    protected $fillable = [
        'id',
        'tenant_id',
        'program_id',
        'customer_id',
        'points_balance',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
