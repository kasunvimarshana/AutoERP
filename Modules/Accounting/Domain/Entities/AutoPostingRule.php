<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * AutoPostingRule entity.
 *
 * Defines rules for automatically generating journal entries when
 * specific business events occur (e.g. purchase receipt, sales shipment).
 *
 * Configured per tenant — all auto-posting logic is metadata-driven.
 */
class AutoPostingRule extends Model
{
    use HasTenant;

    protected $table = 'auto_posting_rules';

    protected $fillable = [
        'tenant_id',
        'name',
        'event_type',
        'debit_account_id',
        'credit_account_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'credit_account_id');
    }
}
