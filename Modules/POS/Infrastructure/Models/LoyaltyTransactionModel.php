<?php

namespace Modules\POS\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTransactionModel extends Model
{
    use HasUuids;

    protected $table = 'pos_loyalty_transactions';

    protected $fillable = [
        'id',
        'tenant_id',
        'card_id',
        'type',
        'points',
        'reference',
        'notes',
    ];
}
