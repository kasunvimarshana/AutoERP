<?php

declare(strict_types=1);

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class InvoiceAdjustment extends TenantOwnedModel
{
    protected $table = 'invoice_adjustments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'source_adjustment_id' => 'integer',
            'source_id' => 'integer',
            'adjustment_type' => AdjustmentType::class,
            'effect' => AdjustmentEffect::class,
            'allocation_method' => AllocationMethod::class,
            'rate' => 'decimal:6',
            'amount' => 'decimal:6',
            'is_system_generated' => 'boolean',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InvoiceAdjustmentAllocation::class, 'invoice_adjustment_id');
    }
}
