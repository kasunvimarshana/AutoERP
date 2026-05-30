<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PaymentAllocationModel extends CoreModel
{
    protected $table = 'payment_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'payment_id' => 'integer',
            'document_id' => 'integer',
            'document_line_id' => 'integer',
            'source_id' => 'integer',
            'source_context' => 'array',
            'allocated_amount' => 'decimal:4',
            'base_allocated_amount' => 'decimal:4',
            'allocation_date' => 'date',
        ]);
    }
}
