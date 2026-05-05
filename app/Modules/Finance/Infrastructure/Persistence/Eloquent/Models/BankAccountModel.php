<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class BankAccountModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'tenant_id', 'account_id', 'name', 'bank_name', 'account_number',
        'routing_number', 'currency_id', 'current_balance', 'last_sync_at',
        'feed_provider', 'feed_credentials_enc', 'is_active',
    ];

    protected $casts = [
        'current_balance' => 'decimal:6',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
