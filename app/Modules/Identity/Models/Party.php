<?php

namespace App\Modules\Identity\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Party extends BaseModel
{
    protected $table = 'parties';

    protected $fillable = [
        'tenant_id',
        'type',
        'code',
        'name',
        'legal_name',
        'tax_number',
        'registration_no',
        'email',
        'phone',
        'website',
        'currency_id',
        'credit_limit',
        'payment_terms_days',
        'is_active',
        'metadata',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:4',
        'payment_terms_days' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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
