<?php

namespace App\Modules\Finance\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentTerm extends BaseModel
{
    protected $table = 'payment_terms';

    protected $fillable = [
        'tenant_id',
        'name',
        'days_due',
        'discount_days',
        'discount_percent',
        'is_active'
    ];

    protected $casts = [
        'days_due' => 'integer',
        'discount_days' => 'integer',
        'is_active' => 'boolean'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
