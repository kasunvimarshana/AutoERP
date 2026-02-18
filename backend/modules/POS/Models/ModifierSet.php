<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Modifier Set Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property array $modifiers
 */
class ModifierSet extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_modifier_sets';

    protected $fillable = [
        'tenant_id',
        'name',
        'modifiers',
    ];

    protected $casts = [
        'modifiers' => 'array',
    ];
}
