<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class CustomerPriceListModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'customer_price_lists';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'price_list_id',
        'priority',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'customer_id' => 'integer',
        'price_list_id' => 'integer',
        'priority' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceListModel::class, 'price_list_id');
    }
}
