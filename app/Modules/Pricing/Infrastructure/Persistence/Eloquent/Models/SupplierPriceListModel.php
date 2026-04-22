<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class SupplierPriceListModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'supplier_price_lists';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'price_list_id',
        'priority',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'supplier_id' => 'integer',
        'price_list_id' => 'integer',
        'priority' => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceListModel::class, 'price_list_id');
    }
}
