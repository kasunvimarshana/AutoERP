<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTransactionPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'pos_transaction_id', 'payment_account_id', 'method', 'amount', 'reference', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'string',
            'metadata' => 'array',
        ];
    }

    public function posTransaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }
}
