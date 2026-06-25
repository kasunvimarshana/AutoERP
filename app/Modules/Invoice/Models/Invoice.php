<?php

declare(strict_types=1);

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\TenantModel;

final class Invoice extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'party_id' => 'integer',
            'currency_id' => 'integer',
            'created_by' => 'integer',
            'approved_by' => 'integer',
            'invoice_type' => InvoiceType::class,
            'direction' => InvoiceDirection::class,
            'status' => InvoiceStatus::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'exchange_rate' => 'decimal:6',
            'subtotal' => 'decimal:6',
            'discount_total' => 'decimal:6',
            'tax_total' => 'decimal:6',
            'charge_total' => 'decimal:6',
            'adjustment_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'paid_total' => 'decimal:6',
            'credit_total' => 'decimal:6',
            'balance_due' => 'decimal:6',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'party_id')->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'party_id')->withTrashed();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'invoice_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(InvoiceSource::class, 'invoice_id');
    }

    public function sourceLines(): HasMany
    {
        return $this->hasMany(InvoiceSourceLine::class, 'invoice_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(InvoiceAdjustment::class, 'invoice_id');
    }

    public function adjustmentAllocations(): HasMany
    {
        return $this->hasMany(InvoiceAdjustmentAllocation::class, 'invoice_id');
    }

    public function balance(): HasOne
    {
        return $this->hasOne(InvoiceBalance::class, 'invoice_id');
    }

    public function creditAllocations(): HasMany
    {
        return $this->hasMany(InvoiceCreditAllocation::class, 'invoice_id');
    }
}
