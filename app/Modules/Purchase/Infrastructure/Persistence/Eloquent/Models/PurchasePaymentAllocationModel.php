<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PurchasePaymentAllocationModel extends CoreModel
{
    protected $table = 'purchase_payment_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'document_id' => 'integer',
            'payment_id' => 'integer',
            'advance_payment_id' => 'integer',
            'allocated_amount' => 'decimal:4',
            'currency_id' => 'integer',
            'base_allocated_amount' => 'decimal:4',
            'allocated_at' => 'datetime',
            'created_by' => 'integer',
        ]);
    }
}
