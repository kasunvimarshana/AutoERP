<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\ResolvesMorphTypeClass;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PaymentAllocationModel extends BaseModel
{
    use HasAudit;
    use HasTenant;
    use ResolvesMorphTypeClass;

    protected $table = 'payment_allocations';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version', 'payment_id',
        'invoice_type', 'invoice_id', 'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:6',
    ];

    public function invoice(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'invoice_type', 'invoice_id');
    }

    public function getInvoiceTypeClassAttribute(): ?string
    {
        return $this->resolveMorphTypeClass($this->invoice_type);
    }
}
