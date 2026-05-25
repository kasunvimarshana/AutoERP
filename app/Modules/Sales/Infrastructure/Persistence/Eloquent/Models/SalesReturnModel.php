<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnHeaderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnLineModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class SalesReturnModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope, SoftDeletes;

    protected $table = 'sales_returns';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'credit_note_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'exchange_rate' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_tax_amount' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'line_restocking_total' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'metadata' => 'array',
            'return_date' => 'date',
            'row_version' => 'integer',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function headerTaxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'header_tax_group_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function originalGdn(): BelongsTo
    {
        return $this->belongsTo(GdnHeaderModel::class, 'original_gdn_id');
    }

    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'original_invoice_id');
    }

    public function originalSalesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrderModel::class, 'original_sales_order_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function salesReturnLines(): HasMany
    {
        return $this->hasMany(SalesReturnLineModel::class, 'sales_return_id');
    }

}
