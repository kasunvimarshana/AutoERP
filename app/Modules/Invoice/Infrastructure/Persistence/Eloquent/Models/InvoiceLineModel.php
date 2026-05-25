<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceReferenceModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class InvoiceLineModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope;

    protected $table = 'invoice_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'item_id' => 'integer',
            'line_total' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
            'metadata' => 'array',
            'quantity' => 'decimal:4',
            'row_version' => 'integer',
            'tax_amount' => 'decimal:4',
            'unit_price' => 'decimal:4',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }

    public function invoiceReference(): BelongsTo
    {
        return $this->belongsTo(InvoiceReferenceModel::class, 'invoice_reference_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

}
