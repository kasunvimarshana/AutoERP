<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePaymentAllocationModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class AdvancePaymentModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope, SoftDeletes;

    protected $table = 'advance_payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'advance_date' => 'date',
            'amount' => 'decimal:4',
            'metadata' => 'array',
            'party_id' => 'integer',
            'remaining_amount' => 'decimal:4',
            'row_version' => 'integer',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PaymentModel::class, 'payment_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function advancePaymentAllocations(): HasMany
    {
        return $this->hasMany(AdvancePaymentAllocationModel::class, 'advance_payment_id');
    }

}
