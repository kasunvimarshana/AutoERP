<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Variation Template Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property array $values
 */
class VariationTemplate extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_variation_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'values',
    ];

    protected $casts = [
        'values' => 'array',
    ];
}
