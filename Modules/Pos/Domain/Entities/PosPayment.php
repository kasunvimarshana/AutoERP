<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PosPayment entity.
 *
 * Amount is cast to string for BCMath precision.
 */
class PosPayment extends Model
{
    use HasTenant;

    protected $table = 'pos_payments';

    protected $fillable = [
        'tenant_id',
        'pos_transaction_id',
        'payment_method',
        'amount',
        'reference',
    ];

    protected $casts = [
        'amount' => 'string',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }
}
