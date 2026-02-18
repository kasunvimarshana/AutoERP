<?php

namespace App\Modules\Procurement\Models;

use App\Models\Tenant;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Procurement\SupplierFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'mobile',
        'company_name',
        'tax_number',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'payment_terms_days',
        'credit_limit',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
