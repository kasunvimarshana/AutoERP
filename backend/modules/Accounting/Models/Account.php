<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Enums\AccountType;
use Modules\Core\Models\BaseModel;

/**
 * Account Model
 *
 * Represents a chart of accounts entry.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $parent_id
 * @property AccountType $type
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $currency_code
 * @property float $balance
 * @property bool $is_active
 * @property bool $is_system
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Account extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'type',
        'code',
        'name',
        'description',
        'currency_code',
        'balance',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get the parent account.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get the child accounts.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Get journal entry lines associated with this account.
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }
}
