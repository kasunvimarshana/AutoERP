<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Payment Method Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $type
 * @property string|null $account_id
 * @property bool $is_active
 * @property int $sort_order
 */
class PaymentMethod extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_payment_methods';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'account_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function transactionPayments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class, 'payment_method_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
