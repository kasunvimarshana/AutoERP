<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Concerns\HasTenant;

class AssetOwnershipModel extends Model
{
    use HasTenant;

    protected $table = 'asset_ownerships';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'party_id',
        'asset_id',
        'ownership_type',
        'acquisition_date',
        'disposal_date',
        'acquisition_cost',
        'notes',
    ];

    protected $casts = [
        'tenant_id'        => 'integer',
        'acquisition_date' => 'datetime',
        'disposal_date'    => 'datetime',
    ];
}
