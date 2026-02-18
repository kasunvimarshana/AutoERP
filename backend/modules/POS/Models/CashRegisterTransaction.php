<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Cash Register Transaction Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $cash_register_id
 * @property string $type
 * @property float $amount
 * @property string $payment_method
 * @property string|null $notes
 * @property string|null $transaction_id
 * @property string $created_by
 */
class CashRegisterTransaction extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_cash_register_transactions';

    protected $fillable = [
        'tenant_id',
        'cash_register_id',
        'type',
        'amount',
        'payment_method',
        'notes',
        'transaction_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
