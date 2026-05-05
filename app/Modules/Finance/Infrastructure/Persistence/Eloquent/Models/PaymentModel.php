<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;

class PaymentModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
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
        'idempotency_key',
        'journal_entry_id',
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'exchange_rate' => 'decimal:10',
        'base_amount' => 'decimal:6',
        'payment_date' => 'date',
        'row_version' => 'integer',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethodModel::class, 'payment_method_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }
}
