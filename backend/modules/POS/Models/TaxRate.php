<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;
use Modules\POS\Enums\TaxCalculationType;

/**
 * Tax Rate Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property float $rate
 * @property bool $is_tax_group
 * @property string|null $tax_group_id
 * @property TaxCalculationType $calculation_type
 * @property bool $is_active
 */
class TaxRate extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_tax_rates';

    protected $fillable = [
        'tenant_id',
        'name',
        'rate',
        'is_tax_group',
        'tax_group_id',
        'calculation_type',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_tax_group' => 'boolean',
        'is_active' => 'boolean',
        'calculation_type' => TaxCalculationType::class,
    ];

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_group_id');
    }

    public function subTaxes(): HasMany
    {
        return $this->hasMany(TaxRate::class, 'tax_group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTaxGroups($query)
    {
        return $query->where('is_tax_group', true);
    }
}
