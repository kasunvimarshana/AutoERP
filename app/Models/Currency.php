<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id', 'code', 'name', 'symbol', 'exchange_rate', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'string', // string for BCMath precision
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
