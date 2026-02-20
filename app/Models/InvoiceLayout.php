<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceLayout extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'header_text', 'footer_text',
        'show_business_name', 'show_location_name', 'show_mobile_number',
        'show_address', 'show_email', 'show_tax_1', 'show_tax_2',
        'show_barcode', 'show_customer', 'show_client_id', 'show_credit_limit',
        'show_expiry_date', 'show_lot_number', 'design',
        'invoice_no_prefix', 'cn_no_prefix', 'is_default', 'module_info',
    ];

    protected function casts(): array
    {
        return [
            'show_business_name' => 'boolean',
            'show_location_name' => 'boolean',
            'show_mobile_number' => 'boolean',
            'show_address' => 'boolean',
            'show_email' => 'boolean',
            'show_tax_1' => 'boolean',
            'show_tax_2' => 'boolean',
            'show_barcode' => 'boolean',
            'show_customer' => 'boolean',
            'show_client_id' => 'boolean',
            'show_credit_limit' => 'boolean',
            'show_expiry_date' => 'boolean',
            'show_lot_number' => 'boolean',
            'is_default' => 'boolean',
            'module_info' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessLocations(): HasMany
    {
        return $this->hasMany(BusinessLocation::class);
    }
}
