<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePaymentModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class AdvancePaymentAllocationModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope;

    protected $table = 'advance_payment_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:4',
            'document_id' => 'integer',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function advancePayment(): BelongsTo
    {
        return $this->belongsTo(AdvancePaymentModel::class, 'advance_payment_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

}
