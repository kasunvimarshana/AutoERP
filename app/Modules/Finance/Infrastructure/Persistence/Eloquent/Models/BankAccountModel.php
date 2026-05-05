<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class BankAccountModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version', 'account_id', 'name',
        'bank_name', 'account_number', 'routing_number', 'currency_id',
        'current_balance', 'last_sync_at', 'feed_provider', 'feed_credentials_enc', 'is_active',
    ];

    protected $casts = [
        'current_balance' => 'decimal:6',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
        'row_version' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }
}
