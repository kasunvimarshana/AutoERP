<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class TransactionTaxModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'transaction_taxes';

    protected $fillable = [
        'tenant_id',
        'reference_type',
        'reference_id',
        'tax_rate_id',
        'taxable_amount',
        'tax_amount',
        'tax_account_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'reference_id' => 'integer',
        'tax_rate_id' => 'integer',
        'tax_account_id' => 'integer',
        'taxable_amount' => 'decimal:6',
        'tax_amount' => 'decimal:6',
    ];

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRateModel::class, 'tax_rate_id');
    }

    public function taxAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'tax_account_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
