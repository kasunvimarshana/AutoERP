<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Concerns\HasTenant;

class PartyModel extends Model
{
    use HasTenant;

    protected $table = 'parties';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'party_type',
        'name',
        'tax_number',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country_code',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_active'  => 'boolean',
    ];
}
