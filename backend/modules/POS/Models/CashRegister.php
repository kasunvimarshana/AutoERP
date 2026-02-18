<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;
use Modules\POS\Enums\CashRegisterStatus;

/**
 * Cash Register Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $location_id
 * @property string $name
 * @property CashRegisterStatus $status
 * @property string|null $user_id
 * @property float $opening_balance
 * @property float|null $closing_balance
 * @property \Carbon\Carbon|null $opened_at
 * @property \Carbon\Carbon|null $closed_at
 */
class CashRegister extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_cash_registers';

    protected $fillable = [
        'tenant_id',
        'location_id',
        'name',
        'status',
        'user_id',
        'opening_balance',
        'closing_balance',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => CashRegisterStatus::class,
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'cash_register_id');
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashRegisterTransaction::class, 'cash_register_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', CashRegisterStatus::OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', CashRegisterStatus::CLOSED);
    }
}
