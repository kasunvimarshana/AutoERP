<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PaymentModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'payment_number',
        'direction',
        'party_type',
        'party_id',
        'payment_method_id',
        'account_id',
        'amount',
        'currency_id',
        'exchange_rate',
        'base_amount',
        'payment_date',
        'status',
        'reference',
        'notes',
        'journal_entry_id',
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'exchange_rate' => 'decimal:10',
        'base_amount' => 'decimal:6',
        'payment_date' => 'date',
    ];
}
