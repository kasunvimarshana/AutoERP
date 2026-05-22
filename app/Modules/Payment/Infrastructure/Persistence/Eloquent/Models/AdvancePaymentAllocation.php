<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class AdvancePaymentAllocation extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'advance_payment_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'allocated_amount' => 'decimal:4',
        ];
    }

    public function advancePayment(): BelongsTo
    {
        return $this->belongsTo(AdvancePayment::class, 'advance_payment_id');
    }
}
