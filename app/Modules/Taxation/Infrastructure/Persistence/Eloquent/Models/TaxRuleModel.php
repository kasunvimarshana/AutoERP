<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class TaxRuleModel extends BaseModel
{
    protected $table = 'tax_rules';

    protected $fillable = [
        'tenant_id',
        'name',
        'tax_rate_id',
        'entity_type',
        'entity_id',
        'jurisdiction',
        'priority',
        'is_active',
        'description',
        'metadata',
    ];

    protected $casts = [
        'tenant_id'   => 'integer',
        'tax_rate_id' => 'integer',
        'entity_id'   => 'integer',
        'priority'    => 'integer',
        'is_active'   => 'boolean',
        'metadata'    => 'array',
    ];

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRateModel::class, 'tax_rate_id');
    }
}
