<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class TaxRateModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'tax_rates';

    protected $fillable = [
        'tenant_id',
        'tax_group_id',
        'name',
        'rate',
        'type',
        'account_id',
        'is_compound',
        'is_active',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'tax_group_id' => 'integer',
        'account_id' => 'integer',
        'is_compound' => 'boolean',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'rate' => 'decimal:6',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function transactionTaxes(): HasMany
    {
        return $this->hasMany(TransactionTaxModel::class, 'tax_rate_id');
    }
}
