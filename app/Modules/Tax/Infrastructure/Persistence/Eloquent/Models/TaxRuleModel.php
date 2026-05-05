<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class TaxRuleModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'tax_rules';

    protected $fillable = [
        'tenant_id',
        'tax_group_id',
        'product_category_id',
        'party_type',
        'region',
        'priority',
        'row_version',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'tax_group_id' => 'integer',
        'product_category_id' => 'integer',
        'priority' => 'integer',
        'row_version' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategoryModel::class, 'product_category_id');
    }
}
