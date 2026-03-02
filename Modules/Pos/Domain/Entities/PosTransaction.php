<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PosTransaction entity.
 *
 * All monetary amounts are cast to string for BCMath precision.
 */
class PosTransaction extends Model
{
    use HasTenant;

    protected $table = 'pos_transactions';

    protected $fillable = [
        'tenant_id',
        'pos_session_id',
        'transaction_number',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'change_due',
        'is_synced',
        'created_offline',
        'completed_at',
    ];

    protected $casts = [
        'subtotal'        => 'string',
        'discount_amount' => 'string',
        'tax_amount'      => 'string',
        'total_amount'    => 'string',
        'paid_amount'     => 'string',
        'change_due'      => 'string',
        'is_synced'       => 'boolean',
        'created_offline' => 'boolean',
        'completed_at'    => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosTransactionLine::class, 'pos_transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class, 'pos_transaction_id');
    }
}
