<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class BankAccountModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'name',
        'account_number',
        'routing_number',
        'bank_name',
        'bank_code',
        'account_type',
        'currency_code',
        'opening_balance',
        'current_balance',
        'credit_limit',
        'status',
        'metadata',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'credit_limit'    => 'decimal:4',
        'metadata'        => 'array',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    /**
     * The corresponding chart-of-accounts entry.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }
}
