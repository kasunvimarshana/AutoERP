<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class AdvancePaymentAllocationModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'advance_payment_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'advance_payment_id' => 'integer',
            'allocated_amount' => 'decimal:4',
            'document_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
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

    public function advancePayment(): BelongsTo
    {
        return $this->belongsTo(AdvancePaymentModel::class, 'advance_payment_id');
    }

    public function document(): MorphTo
    {
        return $this->morphTo();
    }
}

