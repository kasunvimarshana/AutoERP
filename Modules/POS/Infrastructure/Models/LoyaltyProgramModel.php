<?php

namespace Modules\POS\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class LoyaltyProgramModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope;

    protected $table = 'pos_loyalty_programs';

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'points_per_currency_unit',
        'redemption_rate',
        'is_active',
        'description',
    ];

    protected $casts = [
        'points_per_currency_unit' => 'string',
        'redemption_rate'          => 'string',
        'is_active'                => 'boolean',
    ];
}
