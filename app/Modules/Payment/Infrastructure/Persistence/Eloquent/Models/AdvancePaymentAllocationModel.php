<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class AdvancePaymentAllocationModel extends CoreModel
{


    protected $table = 'advance_payment_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'advance_payment_id' => 'integer',
            'document_id' => 'integer',
            'allocated_amount' => 'decimal:4'
        ]);
    }
}