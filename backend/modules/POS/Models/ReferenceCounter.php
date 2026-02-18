<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Reference Counter Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $reference_type
 * @property string|null $prefix
 * @property int $current_number
 * @property int $padding
 */
class ReferenceCounter extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_reference_counters';

    protected $fillable = [
        'tenant_id',
        'reference_type',
        'prefix',
        'current_number',
        'padding',
    ];

    protected $casts = [
        'current_number' => 'integer',
        'padding' => 'integer',
    ];

    public $timestamps = true;
}
