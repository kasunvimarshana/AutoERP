<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Business Location Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $zip_code
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $invoice_scheme_id
 * @property string|null $invoice_layout_id
 * @property bool $is_active
 * @property array|null $settings
 */
class BusinessLocation extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_business_locations';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'phone',
        'email',
        'invoice_scheme_id',
        'invoice_layout_id',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class, 'location_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'location_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'location_id');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'location_id');
    }

    public function restaurantTables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class, 'location_id');
    }

    public function invoiceScheme(): BelongsTo
    {
        return $this->belongsTo(InvoiceScheme::class, 'invoice_scheme_id');
    }

    public function invoiceLayout(): BelongsTo
    {
        return $this->belongsTo(InvoiceLayout::class, 'invoice_layout_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
