<?php

namespace App\Modules\Finance\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankAccount extends BaseModel
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'name',
        'account_number',
        'bank_name',
        'iban',
        'swift',
        'type',
        'currency_id',
        'opening_balance',
        'is_active',
        'last_synced_at'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Currency::class, 'currency_id');
    }
}
