<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class AdvancePaymentModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'advance_payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'party_id' => 'integer',
            'amount' => 'decimal:4',
            'remaining_amount' => 'decimal:4',
            'advance_date' => 'date',
            'payment_id' => 'integer',
            'created_by' => 'integer'
        ]);
    }
}