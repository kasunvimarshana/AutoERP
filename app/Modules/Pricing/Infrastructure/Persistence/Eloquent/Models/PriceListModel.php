<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PriceListModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'price_lists';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'currency_id',
        'is_default',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'currency_id' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItemModel::class, 'price_list_id');
    }

    public function customerAssignments(): HasMany
    {
        return $this->hasMany(CustomerPriceListModel::class, 'price_list_id');
    }

    public function supplierAssignments(): HasMany
    {
        return $this->hasMany(SupplierPriceListModel::class, 'price_list_id');
    }
}
