<?php

namespace App\Modules\Warehouse\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Warehouse extends BaseModel
{
    protected $table = 'warehouses';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'code',
        'name',
        'type',
        'address_line1',
        'city',
        'country_code',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Organization::class, 'org_unit_id');
    }
}
