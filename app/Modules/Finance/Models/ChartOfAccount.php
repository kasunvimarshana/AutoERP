<?php

namespace App\Modules\Finance\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChartOfAccount extends BaseModel
{
    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'sub_type',
        'normal_balance',
        'is_bank',
        'is_control',
        'currency_id',
        'level',
        'path',
        'is_leaf',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_bank' => 'boolean',
        'is_control' => 'boolean',
        'level' => 'integer',
        'is_leaf' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Currency::class, 'currency_id');
    }
}
