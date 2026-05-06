<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class CustomerPriceListModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'customer_price_lists';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'customer_id',
        'price_list_id',
        'priority',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
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

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'org_unit_id');
    }
}
