<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * ChartOfAccount entity.
 *
 * Represents a single account in the tenant's chart of accounts.
 * Supports hierarchical account structures via self-referencing parent/children.
 */
class ChartOfAccount extends Model
{
    use HasTenant;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'tenant_id',
        'account_type_id',
        'parent_id',
        'name',
        'code',
        'description',
        'currency_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }
}
