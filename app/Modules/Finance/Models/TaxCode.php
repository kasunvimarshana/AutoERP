<?php

namespace App\Modules\Finance\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TaxCode extends BaseModel
{
    protected $table = 'tax_codes';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'rate',
        'type',
        'account_id',
        'is_compound',
        'is_active'
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }
}
