<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class InvoiceLineModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'invoice_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'discount_amount' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'invoice_id' => 'integer',
            'invoice_reference_id' => 'integer',
            'item_id' => 'integer',
            'line_total' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'quantity' => 'decimal:4',
            'row_version' => 'integer',
            'tax_amount' => 'decimal:4',
            'tax_group_id' => 'integer',
            'tenant_id' => 'integer',
            'unit_price' => 'decimal:4',
            'uom_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }

    public function invoiceReference(): BelongsTo
    {
        return $this->belongsTo(InvoiceReferenceModel::class, 'invoice_reference_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}
