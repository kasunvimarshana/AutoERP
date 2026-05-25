<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PaymentModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'party_id' => 'integer',
            'payment_date' => 'date',
            'amount' => 'decimal:4',
            'payment_method_id' => 'integer',
            'account_id' => 'integer',
            'currency_id' => 'integer',
            'exchange_rate' => 'decimal:4',
            'base_amount' => 'decimal:4',
            'journal_entry_id' => 'integer'
        ]);
    }
}