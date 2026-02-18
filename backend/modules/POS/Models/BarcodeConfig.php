<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Barcode Configuration Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string|null $description
 * @property int $width
 * @property int $height
 * @property int $top_margin
 * @property int $left_margin
 * @property int $row_distance
 * @property int $col_distance
 * @property int $stickers_in_one_row
 * @property bool $is_default
 * @property bool $is_continuous
 * @property array|null $paper_size
 */
class BarcodeConfig extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_barcode_config';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'width',
        'height',
        'top_margin',
        'left_margin',
        'row_distance',
        'col_distance',
        'stickers_in_one_row',
        'is_default',
        'is_continuous',
        'paper_size',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'top_margin' => 'integer',
        'left_margin' => 'integer',
        'row_distance' => 'integer',
        'col_distance' => 'integer',
        'stickers_in_one_row' => 'integer',
        'is_default' => 'boolean',
        'is_continuous' => 'boolean',
        'paper_size' => 'array',
    ];

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
