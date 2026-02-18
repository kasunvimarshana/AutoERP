<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Transaction Payment Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $transaction_id
 * @property string $payment_method_id
 * @property float $amount
 * @property \Carbon\Carbon $payment_date
 * @property string|null $payment_reference
 * @property string|null $notes
 * @property string $created_by
 */
class TransactionPayment extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_transaction_payments';

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'payment_method_id',
        'amount',
        'payment_date',
        'payment_reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
