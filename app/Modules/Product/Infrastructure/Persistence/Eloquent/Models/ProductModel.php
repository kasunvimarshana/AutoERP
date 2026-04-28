<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;

class ProductModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'row_version',
        'category_id',
        'brand_id',
        'org_unit_id',
        'type',
        'name',
        'image_path',
        'slug',
        'sku',
        'description',
        'base_uom_id',
        'purchase_uom_id',
        'sales_uom_id',
        'tax_group_id',
        'uom_conversion_factor',
        'is_batch_tracked',
        'is_lot_tracked',
        'is_serial_tracked',
        'valuation_method',
        'standard_cost',
        'income_account_id',
        'cogs_account_id',
        'inventory_account_id',
        'expense_account_id',
        'is_active',
        'metadata',
        'purchase_price',
        'sales_price',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'row_version' => 'integer',
        'category_id' => 'integer',
        'brand_id' => 'integer',
        'org_unit_id' => 'integer',
        'base_uom_id' => 'integer',
        'purchase_uom_id' => 'integer',
        'sales_uom_id' => 'integer',
        'tax_group_id' => 'integer',
        'metadata' => 'array',
        'is_batch_tracked' => 'boolean',
        'is_lot_tracked' => 'boolean',
        'is_serial_tracked' => 'boolean',
        'is_active' => 'boolean',
        'uom_conversion_factor' => 'decimal:10',
        'standard_cost' => 'decimal:6',
        'purchase_price' => 'decimal:6',
        'sales_price' => 'decimal:6',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategoryModel::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrandModel::class, 'brand_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'base_uom_id');
    }

    public function purchaseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'purchase_uom_id');
    }

    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'sales_uom_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'income_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'cogs_account_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'inventory_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'expense_account_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariantModel::class, 'product_id');
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(ProductIdentifierModel::class, 'product_id');
    }

    public function variantAttributes(): HasMany
    {
        return $this->hasMany(VariantAttributeModel::class, 'product_id');
    }
}
